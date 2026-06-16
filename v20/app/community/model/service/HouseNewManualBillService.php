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
        $roomInfo = $roomId > 0 ? $this->getRoomInfo($villageId, $roomId) : [];
        if ($roomInfo) {
            $roomInfo['delivery_date'] = !empty($roomInfo['delivery_time']) ? date('Y-m-d', intval($roomInfo['delivery_time'])) : '';
            $roomInfo['property_service_start_date'] = !empty($roomInfo['property_service_start_time']) ? date('Y-m-d', intval($roomInfo['property_service_start_time'])) : '';
            $roomInfo['property_service_end_date'] = !empty($roomInfo['property_service_end_time']) ? date('Y-m-d', intval($roomInfo['property_service_end_time'])) : '';
            $defaultDates = $this->getDefaultBillDates($villageId, $roomInfo);
            $roomInfo['default_bill_start_date'] = $defaultDates['start_date'];
            $roomInfo['default_bill_end_date'] = $defaultDates['end_date'];
        }
        return [
            'room_info' => $roomInfo,
            'project_list' => $this->getProjectList($villageId),
            'vacancy_fee_config' => $this->getVacancyFeeConfig($adminUser),
        ];
    }

    public function getMonthlyRuleList($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $projectId = intval($params['charge_project_id'] ?? 0);
        if ($projectId <= 0) {
            throw new \Exception('请选择收费项目');
        }
        return Db::name('house_new_charge_rule')->alias('r')
            ->leftJoin('house_new_charge_project p', 'p.id = r.charge_project_id')
            ->where([
                ['r.charge_project_id', '=', $projectId],
                ['r.status', '=', 1],
                ['r.bill_create_set', '=', 2],
                ['p.village_id', '=', $villageId],
            ])->field('r.id,r.charge_name,r.charge_price,r.bill_create_set,r.charge_valid_time')
            ->order('r.id DESC')->select()->toArray();
    }

    public function getManualBillRuleInfo($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $roomInfo = $this->getRoomInfo($villageId, intval($params['room_id'] ?? 0));
        $ruleInfo = $this->getRuleInfo($villageId, intval($params['project_id'] ?? 0), intval($params['rule_id'] ?? 0));
        $this->assertMonthlyRule($ruleInfo);
        return $this->formatManualRuleInfo($roomInfo, $ruleInfo);
    }

    public function calcCreateBillMoney($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $roomInfo = $this->getRoomInfo($villageId, $roomId);
        $startDate = trim($params['service_start_time'] ?? '');
        $endDate = trim($params['service_end_time'] ?? '');
        $generateMode = trim($params['generate_mode'] ?? 'day');
        if ($generateMode !== 'day') {
            throw new \Exception('当前仅支持按天生成账单');
        }
        $ruleInfo = $this->getRuleInfo($villageId, intval($params['project_id'] ?? 0), intval($params['rule_id'] ?? 0));
        $this->assertMonthlyRule($ruleInfo);
        $unitPrice = $this->money4($ruleInfo['charge_price'] ?? 0);
        $area = $this->money4($roomInfo['housesize'] ?? 0);
        [$startDate, $endDate] = $this->normalizeBillDates($roomInfo, $startDate, $endDate);
        $preciseMoney = $this->calcDailyMoney($unitPrice, $area, $startDate, $endDate);
        $totalMoney = round($preciseMoney, 1);
        return [
            'days' => $this->daysInclusive($startDate, $endDate),
            'service_start_time' => $startDate,
            'service_end_time' => $endDate,
            'area' => $area,
            'unit_price' => $unitPrice,
            'total_money' => $totalMoney,
            'precise_money' => $preciseMoney,
            'owner_pay_money' => $totalMoney,
            'rule_info' => $this->formatManualRuleInfo($roomInfo, $ruleInfo),
        ];
    }

    public function createManualBill($adminUser, $params)
    {
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
            : $this->buildMonthlyBillSegments($calc['unit_price'], $calc['area'], $calc['service_start_time'], $calc['service_end_time']);
        $orderIds = [];
        Db::startTrans();
        try {
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
                    'number' => $calc['area'],
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
        $frozen = Db::name('house_new_bill_deposit_freeze')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', $roomId],
            ['status', '=', 0],
        ])->sum('freeze_money');
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
            'pigcms_id' => 0,
            'uid' => 0,
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
            ->field('r.*,p.name as order_name,p.id as charge_project_id,n.charge_type')
            ->find();
        if (!$info) {
            throw new \Exception('收费标准不存在');
        }
        if (empty($info['charge_type'])) {
            $info['charge_type'] = self::ORDER_TYPE_VACANCY_FEE;
        }
        return $info;
    }

    private function assertMonthlyRule($ruleInfo)
    {
        if (intval($ruleInfo['bill_create_set'] ?? 0) !== 2) {
            throw new \Exception('新增账单只允许使用按月生成的收费标准');
        }
    }

    private function formatManualRuleInfo($roomInfo, $ruleInfo)
    {
        $feesTypeMap = [0 => '自定义金额', 1 => '单价计量单位', 2 => '单价乘面积'];
        $unitMap = [1 => '房屋面积', 2 => '固定单位', 3 => '车位面积', 4 => '供暖面积', 5 => '计费面积'];
        $billTypeMap = [1 => '后付费', 2 => '预生成'];
        return [
            'charge_name' => $ruleInfo['charge_name'] ?? '',
            'not_house_rate' => $this->money1($ruleInfo['not_house_rate'] ?? 100) . '%',
            'charge_valid_time' => !empty($ruleInfo['charge_valid_time']) ? date('Y-m-d', intval($ruleInfo['charge_valid_time'])) : '长期有效',
            'fees_type_text' => $feesTypeMap[intval($ruleInfo['fees_type'] ?? 1)] ?? '单价计量单位',
            'unit_gage_text' => $unitMap[intval($ruleInfo['unit_gage_type'] ?? 1)] ?? '',
            'charge_price' => $this->money1($ruleInfo['charge_price'] ?? 0),
            'cyclicity_text' => intval($ruleInfo['cyclicity_set'] ?? 0) > 0 ? intval($ruleInfo['cyclicity_set']) . '个月' : '无限期',
            'bill_create_set_text' => '按月生成',
            'bill_type_text' => $billTypeMap[intval($ruleInfo['bill_type'] ?? 2)] ?? '预生成',
            'create_mode_text' => '手动生成',
            'is_prepaid_text' => intval($ruleInfo['is_prepaid'] ?? 2) === 1 ? '支持' : '不支持',
            'area' => $this->money1($roomInfo['housesize'] ?? 0),
            'monthly_unit_price' => $this->money1($ruleInfo['charge_price'] ?? 0),
        ];
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
        return $room;
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
        if (!$start || !$end || $end < $start) {
            throw new \Exception('账单时间不正确');
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
        $overlap = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', $roomId],
            ['project_id', '=', $projectId],
            ['is_paid', 'in', [1, 2]],
            ['is_discard', '=', 1],
            ['is_refund', '=', 1],
            ['service_start_time', '<=', $endTime],
            ['service_end_time', '>=', $startTime],
        ])->field('order_id,service_start_time,service_end_time')->lock(true)->find();
        if ($overlap) {
            $existingStart = date('Y-m-d', intval($overlap['service_start_time']));
            $existingEnd = date('Y-m-d', intval($overlap['service_end_time']));
            throw new \Exception('当前收费项目在' . $existingStart . '至' . $existingEnd . '已存在应缴或已缴账单，不能重复创建');
        }
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

    private function getProjectList($villageId)
    {
        return Db::name('house_new_charge_project')->where([
            ['village_id', '=', $villageId],
            ['status', '<>', 4],
        ])->field('id,name')->order('id DESC')->select()->toArray();
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
