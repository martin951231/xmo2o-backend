<?php

namespace app\community\model\service;

use think\facade\Db;

class HouseNewManualBillService
{
    const ORDER_TYPE_VACANCY_FEE = 'vacancy_fee';

    public function getCreateBillMeta($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $roomId = intval($params['room_id'] ?? 0);
        $roomInfo = $roomId > 0 ? $this->getRoomInfo($villageId, $roomId) : [];
        if ($roomInfo) {
            $roomInfo['delivery_date'] = !empty($roomInfo['delivery_time']) ? date('Y-m-d', intval($roomInfo['delivery_time'])) : '';
            $roomInfo['property_service_start_date'] = !empty($roomInfo['property_service_start_time']) ? date('Y-m-d', intval($roomInfo['property_service_start_time'])) : '';
            $roomInfo['property_service_end_date'] = !empty($roomInfo['property_service_end_time']) ? date('Y-m-d', intval($roomInfo['property_service_end_time'])) : '';
        }
        return [
            'room_info' => $roomInfo,
            'project_list' => $this->getProjectList($villageId),
            'third_company_list' => $this->thirdPartyCompanyList($adminUser, ['status' => 1])['list'],
            'vacancy_fee_config' => $this->getVacancyFeeConfig($adminUser),
        ];
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
        $unitPrice = $this->money4($params['unit_price'] ?? 0);
        $area = isset($params['area']) && $params['area'] !== '' ? $this->money4($params['area']) : $this->money4($roomInfo['housesize'] ?? 0);
        [$startDate, $endDate] = $this->normalizeBillDates($roomInfo, $startDate, $endDate);
        $totalMoney = $this->calcDailyMoney($unitPrice, $area, $startDate, $endDate);
        $thirdMoney = $this->money4($params['third_party_money'] ?? 0);
        if ($thirdMoney < 0 || $thirdMoney > $totalMoney) {
            throw new \Exception('第三方承担金额不能大于账单总金额');
        }
        return [
            'days' => $this->daysInclusive($startDate, $endDate),
            'service_start_time' => $startDate,
            'service_end_time' => $endDate,
            'area' => $area,
            'unit_price' => $unitPrice,
            'total_money' => $totalMoney,
            'third_party_money' => $thirdMoney,
            'owner_pay_money' => $this->money4($totalMoney - $thirdMoney),
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
        $calc = $this->calcCreateBillMoney($adminUser, $params);
        $allowDepositDeduct = isset($params['allow_deposit_deduct']) ? intval($params['allow_deposit_deduct']) : 1;
        $thirdCompanyId = intval($params['third_party_company_id'] ?? 0);
        $thirdMoney = $this->money4($params['third_party_money'] ?? 0);
        $thirdCompany = [];
        if ($thirdMoney > 0) {
            if ($thirdCompanyId <= 0) {
                throw new \Exception('请选择第三方承担公司');
            }
            $thirdCompany = Db::name('house_new_bill_third_party_company')->where([
                ['id', '=', $thirdCompanyId],
                ['village_id', '=', $villageId],
                ['is_del', '=', 0],
                ['status', '=', 1],
            ])->find();
            if (!$thirdCompany) {
                throw new \Exception('第三方承担公司不存在或已禁用');
            }
        }
        $ownerPayMoney = $calc['owner_pay_money'];
        if ($ownerPayMoney < 0) {
            throw new \Exception('业主应付金额不能小于0');
        }
        $now = time();
        $orderData = $this->buildBaseOrderData($adminUser, $roomInfo, $ruleInfo, [
            'project_id' => $projectId,
            'rule_id' => $ruleId,
            'total_money' => $ownerPayMoney,
            'service_start_time' => strtotime($calc['service_start_time'] . ' 00:00:00'),
            'service_end_time' => strtotime($calc['service_end_time'] . ' 23:59:59'),
            'unit_price' => $calc['unit_price'],
            'number' => $calc['area'],
            'allow_deposit_deduct' => $allowDepositDeduct,
            'source_type' => 'manual_new_bill',
            'extra_data' => [
                'bill_days' => $calc['days'],
                'calc_total_money' => $calc['total_money'],
                'third_party_money' => $thirdMoney,
                'remark' => trim($params['remark'] ?? ''),
            ],
        ]);
        Db::startTrans();
        try {
            $orderId = Db::name('house_new_pay_order')->insertGetId($orderData);
            if ($thirdMoney > 0) {
                Db::name('house_new_bill_third_party_split')->insert([
                    'village_id' => $villageId,
                    'property_id' => intval($orderData['property_id'] ?? 0),
                    'order_id' => $orderId,
                    'company_id' => $thirdCompanyId,
                    'company_name' => $thirdCompany['company_name'],
                    'total_money' => $calc['total_money'],
                    'third_party_money' => $thirdMoney,
                    'owner_pay_money' => $ownerPayMoney,
                    'status' => 0,
                    'remark' => trim($params['third_party_remark'] ?? ''),
                    'add_time' => $now,
                    'update_time' => $now,
                ]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return ['order_id' => $orderId, 'msg' => '账单已生成'];
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
        $deliveryTime = intval($roomInfo['delivery_time'] ?? 0);
        if ($deliveryTime <= 0) {
            throw new \Exception('当前房间未填写交房时间');
        }
        $deliveryDay = strtotime(date('Y-m-d 00:00:00', $deliveryTime));
        if ($start < $deliveryDay) {
            throw new \Exception('账单开始日期不得早于交房日期');
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
}
