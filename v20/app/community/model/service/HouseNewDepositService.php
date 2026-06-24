<?php

namespace app\community\model\service;

use think\facade\Db;

class HouseNewDepositService
{
    const ORDER_TYPE_DEPOSIT = 'deposit_bill';

    public function categoryList($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, intval($params['page_size'] ?? ($params['limit'] ?? 10)));
        $where = [
            ['village_id', '=', $villageId],
            ['is_del', '=', 0],
        ];
        $count = Db::name('house_new_deposit_category')->where($where)->count();
        $list = Db::name('house_new_deposit_category')->where($where)
            ->field('id,cate_name,status,add_time,update_time')
            ->page($page, $limit)
            ->order('id DESC')
            ->select()
            ->toArray();
        foreach ($list as &$item) {
            $item['add_time_text'] = !empty($item['add_time']) ? date('Y-m-d H:i:s', intval($item['add_time'])) : '';
            $item['update_time_text'] = !empty($item['update_time']) ? date('Y-m-d H:i:s', intval($item['update_time'])) : '';
        }
        return [
            'list' => $list,
            'count' => $count,
            'total_limit' => $limit,
        ];
    }

    public function categoryOptions($adminUser)
    {
        $villageId = $this->getVillageId($adminUser);
        $list = Db::name('house_new_deposit_category')->where([
            ['village_id', '=', $villageId],
            ['status', '=', 1],
            ['is_del', '=', 0],
        ])->field('id,cate_name')->order('id DESC')->select()->toArray();
        return ['list' => $list];
    }

    public function saveCategory($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $propertyId = $this->getPropertyId($villageId, $adminUser);
        $id = intval($params['id'] ?? 0);
        $cateName = trim($params['cate_name'] ?? '');
        if ($cateName === '') {
            throw new \Exception('请输入押金分类名');
        }
        $repeatQuery = Db::name('house_new_deposit_category')->where([
            ['village_id', '=', $villageId],
            ['cate_name', '=', $cateName],
            ['is_del', '=', 0],
        ]);
        if ($id > 0) {
            $repeatQuery->where('id', '<>', $id);
        }
        $repeat = $repeatQuery->find();
        if ($repeat) {
            throw new \Exception('押金分类名已存在');
        }
        $now = time();
        $save = [
            'property_id' => $propertyId,
            'village_id' => $villageId,
            'cate_name' => $cateName,
            'status' => intval($params['status'] ?? 1) === 1 ? 1 : 0,
            'update_time' => $now,
        ];
        if ($id > 0) {
            $this->getCategory($villageId, $id, false);
            Db::name('house_new_deposit_category')->where('id', $id)->update($save);
        } else {
            $save['add_time'] = $now;
            $id = Db::name('house_new_deposit_category')->insertGetId($save);
        }
        return ['id' => $id];
    }

    public function changeCategoryStatus($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $id = intval($params['id'] ?? 0);
        $status = intval($params['status'] ?? 0) === 1 ? 1 : 0;
        $this->getCategory($villageId, $id, false);
        Db::name('house_new_deposit_category')->where('id', $id)->update([
            'status' => $status,
            'update_time' => time(),
        ]);
        return ['id' => $id, 'status' => $status];
    }

    public function deleteCategory($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $id = intval($params['id'] ?? 0);
        $this->getCategory($villageId, $id, false);
        Db::name('house_new_deposit_category')->where('id', $id)->update([
            'is_del' => 1,
            'update_time' => time(),
        ]);
        return ['id' => $id];
    }

    public function createDepositBill($adminUser, $params)
    {
        $villageId = $this->getVillageId($adminUser);
        $propertyId = $this->getPropertyId($villageId, $adminUser);
        $roomInfo = $this->getRoomInfo($villageId, intval($params['room_id'] ?? 0));
        $category = $this->getCategory($villageId, intval($params['category_id'] ?? 0), true);
        $money = round(floatval($params['deposit_money'] ?? 0), 1);
        if ($money <= 0) {
            throw new \Exception('请输入押金金额');
        }
        [$startTime, $endTime] = $this->normalizeBillTime($params['service_start_time'] ?? '', $params['service_end_time'] ?? '');
        $now = time();
        Db::startTrans();
        try {
            $extraData = [
                'deposit_category_id' => intval($category['id']),
                'deposit_category_name' => $category['cate_name'],
                'remark' => trim($params['remark'] ?? ''),
            ];
            $orderId = Db::name('house_new_pay_order')->insertGetId([
                'village_id' => $villageId,
                'property_id' => $propertyId,
                'room_id' => intval($roomInfo['pigcms_id']),
                'pigcms_id' => 0,
                'uid' => 0,
                'name' => $roomInfo['name'] ?? '',
                'phone' => $roomInfo['phone'] ?? '',
                'position_id' => 0,
                'project_id' => 0,
                'rule_id' => 0,
                'order_type' => self::ORDER_TYPE_DEPOSIT,
                'order_name' => $category['cate_name'],
                'order_no' => '',
                'total_money' => $money,
                'modify_money' => $money,
                'pay_money' => 0,
                'is_paid' => 2,
                'is_discard' => 1,
                'is_refund' => 1,
                'is_prepare' => 2,
                'service_start_time' => $startTime,
                'service_end_time' => $endTime,
                'unit_price' => $money,
                'number' => 1,
                'role_id' => intval($adminUser['wid'] ?? 0),
                'from' => 1,
                'add_time' => $now,
                'update_time' => $now,
                'allow_deposit_deduct' => 0,
                'source_type' => self::ORDER_TYPE_DEPOSIT,
                'extra_data' => json_encode($extraData, JSON_UNESCAPED_UNICODE),
            ]);
            Db::name('house_new_bill_deposit_order')->insert([
                'property_id' => $propertyId,
                'village_id' => $villageId,
                'room_id' => intval($roomInfo['pigcms_id']),
                'order_id' => $orderId,
                'category_id' => intval($category['id']),
                'category_name' => $category['cate_name'],
                'deposit_money' => $money,
                'refunded_money' => 0,
                'account_status' => 0,
                'remark' => trim($params['remark'] ?? ''),
                'add_time' => $now,
                'update_time' => $now,
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return ['order_id' => $orderId];
    }

    public function syncPaidDepositBills($adminUser, $orderList)
    {
        $villageId = $this->getVillageId($adminUser);
        $orderIds = $this->normalizeOrderIds($orderList);
        return $this->syncPaidDepositBillIds($villageId, $orderIds);
    }

    public function syncPaidDepositBillsBySummary($adminUser, $summaryId)
    {
        $villageId = $this->getVillageId($adminUser);
        $summaryId = intval($summaryId);
        if ($summaryId <= 0) {
            return ['count' => 0];
        }
        $orderIds = Db::name('house_new_pay_order')->where([
            ['village_id', '=', $villageId],
            ['summary_id', '=', $summaryId],
        ])->column('order_id');
        return $this->syncPaidDepositBillIds($villageId, $orderIds);
    }

    public function validateDepositBillRefund($adminUser, $orderId, $refundMoney)
    {
        $villageId = $this->getVillageId($adminUser);
        $detail = $this->getDepositOrderDetail($villageId, $orderId);
        if (!$detail || intval($detail['account_status']) !== 1) {
            return true;
        }
        $account = Db::name('house_new_bill_deposit_account')->where([
            ['village_id', '=', $villageId],
            ['room_id', '=', intval($detail['room_id'])],
        ])->find();
        $available = $account ? $this->money4($account['total_money'] - $account['used_money']) : 0;
        if ($this->money4($refundMoney) > $available) {
            throw new \Exception('当前押金已被抵扣使用，剩余可退押金不足');
        }
        return true;
    }

    public function applyDepositBillRefund($adminUser, $orderId, $refundMoney)
    {
        $villageId = $this->getVillageId($adminUser);
        $detail = $this->getDepositOrderDetail($villageId, $orderId);
        if (!$detail || intval($detail['account_status']) !== 1) {
            return ['id' => 0];
        }
        $refundMoney = $this->money4($refundMoney);
        if ($refundMoney <= 0) {
            return ['id' => intval($detail['id'])];
        }
        $now = time();
        Db::startTrans();
        try {
            $account = Db::name('house_new_bill_deposit_account')->where([
                ['village_id', '=', $villageId],
                ['room_id', '=', intval($detail['room_id'])],
            ])->lock(true)->find();
            if (!$account) {
                throw new \Exception('押金账户不存在');
            }
            $available = $this->money4($account['total_money'] - $account['used_money']);
            if ($refundMoney > $available) {
                throw new \Exception('当前押金已被抵扣使用，剩余可退押金不足');
            }
            $refundedMoney = $this->money4($detail['refunded_money'] + $refundMoney);
            $depositMoney = $this->money4($detail['deposit_money']);
            Db::name('house_new_bill_deposit_account')->where('id', $account['id'])->update([
                'total_money' => $this->money4($account['total_money'] - $refundMoney),
                'update_time' => $now,
            ]);
            Db::name('house_new_bill_deposit_order')->where('id', $detail['id'])->update([
                'refunded_money' => $refundedMoney,
                'account_status' => $refundedMoney >= $depositMoney ? 2 : 1,
                'update_time' => $now,
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return ['id' => intval($detail['id'])];
    }

    private function syncPaidDepositBillIds($villageId, $orderIds)
    {
        if (!$orderIds) {
            return ['count' => 0];
        }
        $rows = Db::name('house_new_bill_deposit_order')->alias('d')
            ->leftJoin('house_new_pay_order o', 'o.order_id = d.order_id')
            ->where([
                ['d.village_id', '=', $villageId],
                ['d.order_id', 'in', $orderIds],
                ['d.account_status', '=', 0],
                ['o.is_paid', '=', 1],
                ['o.is_discard', '=', 1],
                ['o.is_refund', '=', 1],
                ['o.source_type', '=', self::ORDER_TYPE_DEPOSIT],
            ])->field('d.*,o.property_id')->select()->toArray();
        if (!$rows) {
            return ['count' => 0];
        }
        $now = time();
        Db::startTrans();
        try {
            foreach ($rows as $row) {
                $account = Db::name('house_new_bill_deposit_account')->where([
                    ['village_id', '=', $villageId],
                    ['room_id', '=', intval($row['room_id'])],
                ])->lock(true)->find();
                if (!$account) {
                    Db::name('house_new_bill_deposit_account')->insert([
                        'property_id' => intval($row['property_id']),
                        'village_id' => $villageId,
                        'room_id' => intval($row['room_id']),
                        'total_money' => $this->money4($row['deposit_money']),
                        'used_money' => 0,
                        'add_time' => $now,
                        'update_time' => $now,
                    ]);
                } else {
                    Db::name('house_new_bill_deposit_account')->where('id', $account['id'])->update([
                        'total_money' => $this->money4($account['total_money'] + $row['deposit_money']),
                        'update_time' => $now,
                    ]);
                }
                Db::name('house_new_bill_deposit_order')->where('id', $row['id'])->update([
                    'account_status' => 1,
                    'update_time' => $now,
                ]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        return ['count' => count($rows)];
    }

    private function getDepositOrderDetail($villageId, $orderId)
    {
        $orderId = intval($orderId);
        if ($orderId <= 0) {
            return [];
        }
        return Db::name('house_new_bill_deposit_order')->where([
            ['village_id', '=', $villageId],
            ['order_id', '=', $orderId],
        ])->find() ?: [];
    }

    private function getCategory($villageId, $id, $mustEnabled)
    {
        if ($id <= 0) {
            throw new \Exception('请选择押金分类');
        }
        $where = [
            ['id', '=', $id],
            ['village_id', '=', $villageId],
            ['is_del', '=', 0],
        ];
        if ($mustEnabled) {
            $where[] = ['status', '=', 1];
        }
        $category = Db::name('house_new_deposit_category')->where($where)->find();
        if (!$category) {
            throw new \Exception($mustEnabled ? '押金分类不存在或已禁用' : '押金分类不存在');
        }
        return $category;
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
        ])->field('pigcms_id,village_id,name,phone')->find();
        if (!$room) {
            throw new \Exception('房间不存在');
        }
        return $room;
    }

    private function normalizeBillTime($startDate, $endDate)
    {
        $startDate = trim($startDate);
        $endDate = trim($endDate);
        if ($startDate === '') {
            $startDate = date('Y-m-d');
        }
        if ($endDate === '') {
            $endDate = $startDate;
        }
        $start = strtotime($startDate . ' 00:00:00');
        $end = strtotime($endDate . ' 23:59:59');
        if (!$start || !$end || $end < $start) {
            throw new \Exception('账单时间不正确');
        }
        return [$start, $end];
    }

    private function normalizeOrderIds($orderList)
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

    private function money4($money)
    {
        return round(floatval($money), 4);
    }
}
