<?php
namespace app\social_activity\model\service;

use think\facade\Db;
use app\common\model\db\PaidOrderRecord;
use app\common\model\service\UserService;

class SocialActivityRefundService
{
    const PENDING = 0;
    const PROCESSING = 1;
    const SUCCESS = 2;
    const FAILED = 3;
    const MANUAL = 4;
    const LABELS = [0 => '待提交', 1 => '处理中', 2 => '退款成功', 3 => '退款失败', 4 => '待人工处理'];

    protected function gateway(array $pay)
    {
        $config = json_decode($pay['pay_config'], true);
        if (!is_array($config)) { throw new \RuntimeException('原支付配置异常'); }
        return new SocialActivityRefundGateway($config);
    }

    protected function enqueue($refundId)
    {
        \think\facade\Queue::later(20, \app\job\SocialActivityRefundQueryJob::class, ['id' => $refundId]);
    }

    public function apply($orderId, $uid)
    {
        return SocialActivityRefundLock::run($orderId, function () use ($orderId, $uid) {
            $refund = Db::transaction(function () use ($orderId, $uid) {
                $order = Db::name('social_activity_order')->where('id', (int)$orderId)->lock(true)->find();
                if (!$order) { throw new \RuntimeException('订单不存在'); }
                if ((int)$order['uid'] !== (int)$uid || !$uid) { throw new \RuntimeException('只能退本人订单'); }
                $existing = Db::name('social_activity_refund')->where('order_id', $orderId)->find();
                if ($existing) { return $existing; }
                SocialActivityRefundPolicy::validate($order, $uid, time());
                $pay = Db::name('pay_order_info')->where('orderid', $order['pay_orderno'])->lock(true)->find();
                if (!$pay || (int)$pay['paid'] !== 1 || $pay['business'] !== 'social_activity' || (int)$pay['business_order_id'] !== (int)$orderId) {
                    throw new \RuntimeException('原支付记录缺失或不匹配，请联系管理员');
                }
                if ((int)$pay['refund_money'] > 0 || (int)$pay['refund_nums'] > 0) {
                    throw new \RuntimeException('原支付单已有退款记录，请联系管理员核对');
                }
                $online = (int)$pay['paid_money'];
                $balance = (int)$pay['current_system_balance'];
                if ($online < 0 || $balance < 0 || $online + $balance <= 0 ||
                    $online !== SocialActivityRefundPolicy::cents($order['money_online_pay']) ||
                    $balance !== SocialActivityRefundPolicy::cents($order['money_system_balance']) ||
                    $online + $balance !== SocialActivityRefundPolicy::cents($order['amount'])) {
                    throw new \RuntimeException('订单实付金额与支付记录不一致，请联系管理员核对');
                }
                if ($online > 0) {
                    if ($pay['channel'] !== 'allinpay' || (float)$pay['rate'] !== 1.0) {
                        throw new \RuntimeException('该订单支付通道暂不支持自助退款，请联系管理员');
                    }
                    $this->gateway($pay)->assertReady();
                }
                $extra = json_decode($pay['paid_extra'], true);
                $trxid = is_array($extra) ? ($extra['trxid'] ?? ($extra['transaction_no'] ?? '')) : $pay['paid_extra'];
                // 原支付单号始终保存；非通联流水不作为 oldtrxid 发送。
                if (!preg_match('/^\d{15,20}$/', (string)$trxid)) { $trxid = ''; }
                $now = date('Y-m-d H:i:s');
                $record = [
                    'order_id' => (int)$orderId, 'uid' => (int)$uid, 'order_sn' => $order['order_sn'],
                    'pay_order_id' => $pay['id'], 'pay_order_no' => $pay['orderid'],
                    'refund_no' => 'SAR' . bin2hex(random_bytes(14)), 'original_trxid' => $trxid,
                    'activity_id' => $order['activity_id'], 'activity_name' => $order['activity_name'],
                    'host_village_id' => $order['host_village_id'], 'host_village_name' => $order['host_village_name'],
                    'refund_amount' => $online + $balance, 'paid_amount' => $online + $balance,
                    'online_amount' => $online, 'balance_amount' => $balance, 'status' => self::PENDING,
                    'reason' => '用户申请活动整单退款', 'source' => 'user', 'created_at' => $now, 'updated_at' => $now,
                ];
                $record['id'] = Db::name('social_activity_refund')->insertGetId($record);
                $changed = Db::name('social_activity_order')->where(['id' => $orderId, 'refund_status' => 0, 'is_sign' => 0, 'status' => 10])->update(['refund_status' => 1]);
                if ($changed !== 1) { throw new \RuntimeException('订单状态已改变，请刷新'); }
                return $record;
            });
            // 只有本次新建记录可以提交。重复请求只返回持久化状态。
            if ((int)$refund['status'] === self::PENDING && empty($refund['submitted_at'])) {
                $this->submitOnce($refund);
            }
            return $this->userStatus($orderId, $uid);
        });
    }

    private function submitOnce(array $refund)
    {
        $now = date('Y-m-d H:i:s');
        // 先标记已尝试发送。即便在发包前进程崩溃，也只能查单/人工核实，不自动重发。
        $won = Db::name('social_activity_refund')->where(['id' => $refund['id'], 'status' => self::PENDING])
            ->whereNull('submitted_at')->update(['status' => self::PROCESSING, 'submitted_at' => $now, 'updated_at' => $now]);
        if (!$won) { return; }
        try {
            if ((int)$refund['online_amount'] === 0) { $this->complete($refund['id'], '', $now); return; }
            $pay = $this->payment($refund);
            $result = $this->gateway($pay)->submit($refund);
            $this->saveResult($refund, $result, false);
        } catch (\Throwable $e) {
            $this->saveError($refund['id'], $e->getMessage());
        }
        // 明确成功/失败不查单；处理中、响应异常或本地入账失败才排队兜底。
        $current = Db::name('social_activity_refund')->where('id', $refund['id'])->find();
        if (config('social_activity_refund.auto_query_enabled', false) && $current && in_array((int)$current['status'], [self::PENDING, self::PROCESSING], true)) {
            try {
                $this->enqueue($refund['id']);
            } catch (\Throwable $e) {
                $this->saveError($refund['id'], '退款查询任务入队失败，请管理员查询状态；' . ($current['failure_reason'] ?? ''));
            }
        }
    }

    private function payment(array $refund)
    {
        $pay = Db::name('pay_order_info')->where('id', $refund['pay_order_id'])->find();
        if (!$pay || $pay['orderid'] !== $refund['pay_order_no'] || $pay['business'] !== 'social_activity' || (int)$pay['business_order_id'] !== (int)$refund['order_id']) {
            throw new \RuntimeException('原支付记录不匹配');
        }
        return $pay;
    }

    public function sync($refundId)
    {
        $refund = Db::name('social_activity_refund')->where('id', (int)$refundId)->find();
        if (!$refund) { throw new \RuntimeException('退款记录不存在'); }
        return SocialActivityRefundLock::run($refund['order_id'], function () use ($refundId) { return $this->syncUnlocked($refundId); });
    }

    private function syncUnlocked($refundId)
    {
        $refund = Db::name('social_activity_refund')->where('id', $refundId)->find();
        if ((int)$refund['status'] === self::SUCCESS) { return $refund; }
        try {
            if ((int)$refund['online_amount'] === 0) {
                $this->complete($refundId, '', date('Y-m-d H:i:s'));
            } else {
                $result = $this->gateway($this->payment($refund))->query($refund);
                $this->saveResult($refund, $result, true);
            }
        } catch (\Throwable $e) {
            $this->saveError($refundId, $e->getMessage());
        }
        Db::name('social_activity_refund')->where('id', $refundId)->update(['last_query_at' => date('Y-m-d H:i:s')]);
        return Db::name('social_activity_refund')->where('id', $refundId)->find();
    }

    private function saveResult(array $refund, array $result, $isQuery)
    {
        $code = (string)($result['trxstatus'] ?? '');
        if ($isQuery && (int)($result['trxamt'] ?? -1) !== (int)$refund['online_amount']) {
            // 查无此单可能没有金额；不能将其当成已失败而重新退款。
            if ($code !== '1001') { throw new \RuntimeException('通联退款金额不匹配'); }
        }
        $status = self::PROCESSING;
        if (substr($code, 0, 1) === '3') { $status = $code === '3888' ? self::MANUAL : self::FAILED; }
        if ($code === '1001') { $status = self::MANUAL; }
        $changes = ['failure_reason' => '', 'status' => $status, 'gateway_code' => $code, 'updated_at' => date('Y-m-d H:i:s')];
        if (!empty($result['trxid'])) { $changes['refund_trxid'] = $result['trxid']; }
        if ($status === self::FAILED || $status === self::MANUAL) {
            $changes['failure_reason'] = mb_substr($result['errmsg'] ?? ('通联状态：' . $code), 0, 500);
        }
        Db::name('social_activity_refund')->where('id', $refund['id'])->where('status', '<>', self::SUCCESS)->update($changes);
        // 网关已验证通信状态、商户及退款单号；退款响应成功即可按原申请金额入账。
        if ($code === '0000') {
            if (empty($result['trxid'])) { throw new \RuntimeException('通联退款流水缺失'); }
            $finish = \DateTime::createFromFormat('YmdHis', $result['fintime'] ?? '');
            $this->complete($refund['id'], $result['trxid'], $finish ? $finish->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'));
        }
    }

    private function saveError($id, $message)
    {
        Db::name('social_activity_refund')->where('id', $id)->where('status', '<>', self::SUCCESS)
            ->update(['failure_reason' => mb_substr($message, 0, 500), 'updated_at' => date('Y-m-d H:i:s')]);
    }

    private function complete($id, $trxid, $finishedAt)
    {
        Db::transaction(function () use ($id, $trxid, $finishedAt) {
            $refund = Db::name('social_activity_refund')->where('id', $id)->lock(true)->find();
            if ((int)$refund['status'] === self::SUCCESS) { return; }
            $order = Db::name('social_activity_order')->where('id', $refund['order_id'])->lock(true)->find();
            $pay = Db::name('pay_order_info')->where('id', $refund['pay_order_id'])->lock(true)->find();
            if (!$order || !$pay || (int)$order['refund_status'] !== 1 || (int)$order['is_sign'] !== 0 || (int)$order['status'] !== 10 || (int)$pay['refund_money'] !== 0) {
                throw new \RuntimeException('退款资金已确认，本地订单状态异常，需人工核对');
            }
            if ((int)$refund['balance_amount'] > 0) {
                // 本地余额和流水与退款单在同一事务中落库，重复回调不会再次入账。
                $user = Db::name('user')->where('uid', $refund['uid'])->lock(true)->find();
                if (!$user) { throw new \RuntimeException('退款用户不存在'); }
                $amount = bcdiv((string)$refund['balance_amount'], '100', 2);
                Db::name('user')->where('uid', $refund['uid'])->inc('now_money', $amount)->update();
                if (!Db::name('user_money_list')->insert(['uid' => $refund['uid'], 'type' => 1, 'money' => $amount,
                    'current_money' => bcadd((string)$user['now_money'], $amount, 2),
                    'time' => time(), 'ip' => request()->ip(), 'desc' => '社群活动整单退款，退款单号' . $refund['refund_no'],
                    'ask' => 0, 'ask_id' => 0, 'withdraw_id' => 0, 'order_no' => $refund['pay_order_no'],
                    'village_id' => $refund['host_village_id']])) {
                    throw new \RuntimeException('退款余额流水写入失败');
                }
            }
            $score = Db::name('user_score_list')->where(['uid' => $refund['uid'], 'order_id' => $refund['order_id'], 'order_type' => 'social_activity_order', 'type' => 1, 'score_type' => 35])->sum('score');
            if ($score > 0) {
                $scoreResult = (new UserService())->userScore($refund['uid'], $score, '社群活动订单退款，扣除赠送积分，订单编号' . $refund['order_sn'], 2,
                    ['order_id' => $refund['order_id'], 'order_type' => 'social_activity_order', 'score_type' => 35]);
                if (!empty($scoreResult['error_code'])) { throw new \RuntimeException('退款积分回退失败'); }
            }
            Db::name('pay_order_info')->where('id', $pay['id'])->update(['refund' => 2, 'refund_money' => $refund['online_amount'], 'refund_nums' => (int)$pay['refund_nums'] + 1, 'refund_last_time' => $finishedAt]);
            (new PaidOrderRecord())->paidOrderRefundHandle(0, $pay['id'], $pay['orderid'], ['refund_status' => 2, 'refund_money' => $refund['online_amount'] / 100, 'last_refund_time' => strtotime($finishedAt)]);
            Db::name('social_activity_order')->where('id', $order['id'])->update(['status' => 20, 'refund_status' => 2, 'cancel_remark' => '用户整单退款成功', 'update_time' => date('Y-m-d H:i:s')]);
            // 报名数、票库存现有实现根据有效订单实时统计，状态20即释放名额。
            Db::name('social_activity_refund')->where('id', $id)->update(['status' => self::SUCCESS, 'refund_trxid' => $trxid,
                'finished_at' => $finishedAt, 'failure_reason' => '', 'updated_at' => date('Y-m-d H:i:s')]);
        });
    }

    public function notify(array $params)
    {
        $no = $params['cusorderid'] ?? ($params['reqsn'] ?? '');
        $refund = Db::name('social_activity_refund')->where('refund_no', $no)->find();
        if (!$refund) { throw new \RuntimeException('退款记录不存在'); }
        $this->gateway($this->payment($refund))->verifyNotification($params);
        if ((int)($params['trxamt'] ?? -1) !== (int)$refund['online_amount']) { throw new \RuntimeException('退款通知金额不匹配'); }
        // 通知只触发查询，不读取其 trxstatus 来入账；最终由直连通联的查单结果确认。
        if ((int)$refund['status'] === self::SUCCESS) { return; }
        if (!empty($refund['last_query_at']) && time() - strtotime($refund['last_query_at']) < 10) {
            throw new \RuntimeException('退款结果正在核对，请稍后通知');
        }
        $result = $this->sync($refund['id']);
        if (!in_array((int)$result['status'], [self::SUCCESS, self::FAILED], true)) { throw new \RuntimeException('等待退款确认'); }
    }

    public function userStatus($orderId, $uid)
    {
        $order = (new SocialActivityParticipantService())->scope(Db::name('social_activity_order'), $uid)->where('id', (int)$orderId)->find();
        if (!$order) { throw new \RuntimeException('订单不存在或无权查看'); }
        $refund = Db::name('social_activity_refund')->where('order_id', (int)$orderId)->find();
        if (!$refund) { throw new \RuntimeException('退款记录不存在'); }
        return SocialActivityRefundPolicy::userResult($refund);
    }

    public function scopedQuery(array $admin)
    {
        if (empty($admin['id']) || (int)($admin['status'] ?? 0) !== 1) { throw new \RuntimeException('无权查看退款'); }
        $query = Db::name('social_activity_refund');
        if ((int)($admin['role_id'] ?? 0) !== 1) {
            $ids = array_values(array_filter(array_map('intval', explode(',', (string)($admin['village_id'] ?? '')))));
            $query->whereIn('host_village_id', $ids ?: [-1]);
        }
        return $query;
    }

    public function updateByAdmin($id, array $admin)
    {
        $record = $this->scopedQuery($admin)->where('id', (int)$id)->find();
        if (!$record) { throw new \RuntimeException('退款不存在或无权查看'); }
        Db::name('social_activity_refund')->where('id', $record['id'])->update(['last_query_admin_id' => (int)$admin['id']]);
        $updated = $this->sync($record['id']);
        return ['id' => $record['id'], 'status' => (int)$updated['status'],
            'status_text' => self::LABELS[(int)$updated['status']], 'failure_reason' => $updated['failure_reason'],
            'last_query_at' => $updated['last_query_at']];
    }

    public function getList(array $params, array $admin)
    {
        $query = $this->scopedQuery($admin);
        foreach (['order_sn', 'pay_order_no', 'refund_no', 'refund_trxid'] as $key) {
            if (!empty($params[$key])) { $query->where($key, trim($params[$key])); }
        }
        foreach (['activity_id', 'host_village_id'] as $key) {
            if (!empty($params[$key])) { $query->where($key, (int)$params[$key]); }
        }
        foreach (['activity_name', 'host_village_name'] as $key) {
            if (!empty($params[$key])) { $query->whereLike($key, '%' . trim($params[$key]) . '%'); }
        }
        if (isset($params['status']) && $params['status'] !== '') { $query->where('status', (int)$params['status']); }
        if (isset($params['order_status']) && $params['order_status'] !== '') {
            $query->whereIn('order_id', Db::name('social_activity_order')->where('status', (int)$params['order_status'])->field('id')->buildSql());
        }
        $timeField = ($params['time_type'] ?? '') === 'finished_at' ? 'finished_at' : 'created_at';
        foreach (['start_time' => '>=', 'end_time' => '<='] as $key => $op) {
            if (!empty($params[$key])) {
                $time = strtotime($params[$key]);
                if (!$time) { throw new \RuntimeException('时间格式错误'); }
                $query->where($timeField, $op, date('Y-m-d H:i:s', $time));
            }
        }
        $result = $query->order('id desc')->paginate(['list_rows' => min(100, max(1, (int)($params['pageSize'] ?? 20))), 'page' => max(1, (int)($params['page'] ?? 1))])->toArray();
        $orderIds = array_column($result['data'], 'order_id');
        $orderStatuses = $orderIds ? Db::name('social_activity_order')->whereIn('id', $orderIds)->column('status', 'id') : [];
        foreach ($result['data'] as &$row) {
            $row['order_status'] = $orderStatuses[$row['order_id']] ?? null;
            $row['order_status_text'] = \app\social_activity\model\db\SocialActivityOrder::STATUS_MAP[$row['order_status']] ?? '订单不存在';
            $row['status_text'] = self::LABELS[(int)$row['status']] ?? '未知';
            foreach (['refund_amount', 'paid_amount', 'online_amount', 'balance_amount'] as $key) { $row[$key] = bcdiv((string)$row[$key], '100', 2); }
        }
        return $result;
    }
}
