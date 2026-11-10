<?php
namespace app\community\model\service;

use think\facade\Db;

/** 周期收费统计：先确定项目下的计费对象，只汇总各对象实际生成的有效账单。 */
class ChargeStatusService
{
    public function getData($villageId, $start, $end)
    {
        $bindings = Db::name('house_new_charge_standard_bind')->alias('b')
            ->join('house_new_charge_project p', 'p.id=b.project_id')
            ->join('house_new_charge_number n', 'n.id=p.subject_id')
            ->join('house_new_charge_rule r', 'r.id=b.rule_id AND r.charge_project_id=p.id')
            ->leftJoin('house_village_user_vacancy v', 'v.pigcms_id=b.vacancy_id AND v.village_id=b.village_id AND v.is_del=0')
            ->leftJoin('house_village_parking_position pp', 'pp.position_id=b.position_id')
            ->where([['b.village_id', '=', $villageId], ['p.village_id', '=', $villageId],
                ['r.village_id', '=', $villageId], ['b.is_del', 'in', [1, 4]], ['p.status', '=', 1],
                ['r.status', 'in', [1, 2, 4]], ['p.type', '=', 2],
                ['n.charge_type', 'in', ['property', 'merchant_property', 'parking_management']]])
            ->field('b.id,b.is_del,r.status as rule_status,b.project_id,b.rule_id,b.bind_type,b.vacancy_id,b.position_id,b.order_add_time,b.custom_value,' .
                'n.charge_type,r.charge_valid_time,r.charge_price,r.bill_create_set,r.fees_type,r.unit_gage_type,' .
                'v.pigcms_id as room_id,v.house_type,v.housesize,pp.position_area')
            ->select()->toArray();
        $bills = [];
        {
            $bills = Db::name('house_new_pay_order')->where([
                ['village_id', '=', $villageId],
                ['is_paid', 'in', [1, 2]], ['is_discard', '=', 1],
                ['service_start_time', '<=', strtotime($end . ' 23:59:59')],
                ['service_end_time', '>=', strtotime($start . ' 00:00:00')],
            ])->field('order_id,project_id,rule_id,room_id,position_id,is_paid,is_discard,total_money,modify_money,pay_money,refund_money,service_start_time,service_end_time')
                ->select()->toArray();
        }
        $projects = Db::name('house_new_charge_project')->alias('p')
            ->join('house_new_charge_number n', 'n.id=p.subject_id')
            ->where([['p.village_id', '=', $villageId], ['p.type', '=', 2]])
            ->column('n.charge_type', 'p.id');
        $rooms = Db::name('house_village_user_vacancy')->where('village_id', $villageId)->column('house_type', 'pigcms_id');
        $cycles = Db::name('house_new_charge_rule')->where('village_id', $villageId)->column('bill_create_set', 'id');
        foreach ($bills as &$bill) {
            $bill['charge_type'] = $projects[$bill['project_id']] ?? '';
            $bill['house_type'] = $rooms[$bill['room_id']] ?? 0;
            $bill['bill_create_set'] = $cycles[$bill['rule_id']] ?? 2;
        }
        unset($bill);
        return $this->calculate($bindings, $bills, $start, $end);
    }

    /** 独立计算，便于用固定账单验证去重、分类和标准切换。 */
    public function calculate(array $bindings, array $bills, $start, $end)
    {
        $start = $this->day($start);
        $end = $this->day($end);
        if (!$start || !$end || $end < $start) {
            throw new \InvalidArgumentException('统计开始日期不能晚于结束日期');
        }
        $rows = [];
        foreach (['property' => '住宅物业费', 'merchant_property' => '商铺物业费', 'parking_management' => '车位管理费'] as $type => $name) {
            $rows[$type] = ['name' => $name, 'charge_type' => $type, 'amound' => 0, 'paid_amound' => 0, 'remaining_paid_amound_num' => 0,
                'accounts' => [], 'paid_accounts' => []];
        }
        $objects = [];
        $activeObjects = [];
        foreach ($bindings as $binding) {
            if (intval($binding['is_del'] ?? 1) !== 1 || intval($binding['rule_status'] ?? 1) !== 1) {
                continue;
            }
            $account = intval($binding['bind_type']) === 2 ? 'position:' . intval($binding['position_id']) : 'room:' . intval($binding['room_id']);
            $key = intval($binding['project_id']) . ':' . $account;
            $effective = max($this->day($binding['charge_valid_time']), $this->day($binding['order_add_time']));
            $activeObjects[$key] = max($activeObjects[$key] ?? 0, $effective);
        }
        foreach ($bindings as $binding) {
            $isPosition = intval($binding['bind_type']) === 2;
            $id = intval($isPosition ? $binding['position_id'] : $binding['room_id']);
            if ($id <= 0) {
                continue;
            }
            $type = $binding['charge_type'];
            // 车位管理费只统计车位绑定；同一房屋下的不同车位分别计数。
            // 房屋绑定不进入 objects，后续已缴户数和金额也不会匹配房屋账单。
            if ($type === 'parking_management' && !$isPosition) {
                continue;
            }
            if (in_array($type, ['property', 'merchant_property'], true)) {
                if ($isPosition || !in_array(intval($binding['house_type']), [1, 2], true)) {
                    continue;
                }
                $type = intval($binding['house_type']) === 2 ? 'merchant_property' : 'property';
            }
            if (!isset($rows[$type])) {
                continue;
            }
            $account = ($isPosition ? 'position:' : 'room:') . $id;
            $key = intval($binding['project_id']) . ':' . $account;
            // 已解绑房屋不重新计入总户数；旧绑定仅用于现有房屋换标之前的历史期间。
            if (!isset($activeObjects[$key])) {
                continue;
            }
            $effective = max($this->day($binding['charge_valid_time']), $this->day($binding['order_add_time']));
            $isHistorical = intval($binding['is_del'] ?? 1) !== 1 || intval($binding['rule_status'] ?? 1) !== 1;
            if ($isHistorical && $effective >= $activeObjects[$key]) {
                continue;
            }
            // 车位可收户数按当前绑定的车位去重统计，不受是否开单影响。
            if ($type === 'parking_management') {
                $rows[$type]['accounts'][$account] = true;
            }
            if (!isset($objects[$key])) {
                $objects[$key] = ['type' => $type, 'account' => $account];
            }
        }
        $ruleCycles = [];
        foreach ($bindings as $binding) {
            $ruleCycles[intval($binding['rule_id'])] = intval($binding['bill_create_set']);
        }
        $seenBills = [];
        foreach ($bills as $bill) {
            if (intval($bill['is_discard']) !== 1 || !in_array(intval($bill['is_paid']), [1, 2], true)) {
                continue;
            }
            if (isset($seenBills[$bill['order_id']])) {
                continue;
            }
            $seenBills[$bill['order_id']] = true;
            // 全额退款的已缴账单不再计入可收、实收及户数。
            if (intval($bill['is_paid']) === 1
                && floatval($bill['refund_money'] ?? 0) > 0
                && floatval($bill['refund_money']) >= floatval($bill['pay_money'])) {
                continue;
            }
            // 车位账单可能同时带房屋ID，优先按车位归属，禁止串入房屋绑定。
            $account = !empty($bill['position_id']) ? 'position:' . intval($bill['position_id']) : 'room:' . intval($bill['room_id']);
            $key = intval($bill['project_id']) . ':' . $account;
            if (!isset($objects[$key])) {
                // 历史账单不依赖当前绑定；项目分类与账单对象共同确定归属。
                $type = $bill['charge_type'] ?? '';
                if ($type === 'parking_management') {
                    if (empty($bill['position_id'])) {
                        continue;
                    }
                } elseif (in_array($type, ['property', 'merchant_property'], true)) {
                    if (!empty($bill['position_id']) || empty($bill['room_id'])) {
                        continue;
                    }
                    $houseType = intval($bill['house_type'] ?? 0);
                    if ($houseType && !in_array($houseType, [1, 2], true)) {
                        continue;
                    }
                    if ($houseType) {
                        $type = $houseType === 2 ? 'merchant_property' : 'property';
                    }
                } else {
                    continue;
                }
                $objects[$key] = ['type' => $type, 'account' => $account];
            }
            $billStart = $this->day($bill['service_start_time']);
            $billEnd = $this->day($bill['service_end_time']);
            if (!$billStart || !$billEnd || $billEnd < $billStart || $billStart > $end || $billEnd < $start) {
                continue;
            }
            $object = $objects[$key];
            // 服务期间与查询范围相交即计入整笔账单，不对跨年账单按月份分摊。
            // modify_money 是账单当前应缴金额，包含已生效的调价；零元调价也必须保留。
            $amount = max(0, floatval($bill['modify_money'] ?? $bill['total_money']));
            $rows[$object['type']]['amound'] += $amount;
            // 未缴金额只汇总未缴账单，已缴账单的退款或优惠不作为欠费。
            if (intval($bill['is_paid']) === 2) {
                $rows[$object['type']]['remaining_paid_amound_num'] += $amount;
            }
            $rows[$object['type']]['accounts'][$object['account']] = true;
            if (intval($bill['is_paid']) === 1) {
                $money = max(0, floatval($bill['pay_money']) - floatval($bill['refund_money'] ?? 0));
                if ($money <= 0 && floatval($bill['refund_money'] ?? 0) > 0) {
                    continue;
                }
                $rows[$object['type']]['paid_amound'] += $money;
                $rows[$object['type']]['paid_accounts'][$object['account']] = true;
            }
        }
        foreach ($rows as &$row) {
            $row['account_num'] = count($row['accounts']);
            $row['paid_account_num'] = count($row['paid_accounts']);
            $row['remaining_paid_account_num'] = $row['account_num'] - $row['paid_account_num'];
            $row['paid_account_rate'] = $row['account_num'] ? round($row['paid_account_num'] / $row['account_num'] * 100, 2) : 0;
            $row['amound'] = round($row['amound'], 2);
            $row['paid_amound'] = round($row['paid_amound'], 2);
            $row['remaining_paid_amound_num'] = round($row['remaining_paid_amound_num'], 2);
            $row['paid_amound_rate'] = $row['amound'] > 0 ? round($row['paid_amound'] / $row['amound'] * 100, 2) : 0;
            unset($row['accounts'], $row['paid_accounts']);
        }
        unset($row);
        return array_values($rows);
    }

    private function day($value)
    {
        if (!$value) {
            return 0;
        }
        $time = is_numeric($value) ? intval($value) : strtotime($value);
        return $time > 0 ? strtotime(date('Y-m-d', $time)) : 0;
    }

    private function periods($start, $end, $cycle)
    {
        if ($cycle === 1) {
            return (new \DateTime(date('Y-m-d', $start)))->diff(new \DateTime(date('Y-m-d', $end)))->days + 1;
        }
        if (!in_array($cycle, [2, 3], true)) {
            throw new \InvalidArgumentException('该收费标准的自定义周期暂不支持统计');
        }
        $months = 0;
        for ($cursor = $start; $cursor <= $end; $cursor = strtotime('first day of next month', $cursor)) {
            $segmentEnd = min($end, strtotime(date('Y-m-t', $cursor)));
            $days = (new \DateTime(date('Y-m-d', $cursor)))->diff(new \DateTime(date('Y-m-d', $segmentEnd)))->days + 1;
            $months += $days / intval(date('t', $cursor));
        }
        return $cycle === 3 ? $months / 12 : $months;
    }
}
