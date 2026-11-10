<?php
// 所有数据在外层事务回滚；网关和队列替换为测试桩，不会请求通联或产生队列任务。
if (!in_array('--allow-test-fixtures', $argv ?? [], true)) { exit("Run only in a test environment with --allow-test-fixtures.\n"); }
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();
use think\facade\Db;
use think\facade\Cache;
use app\social_activity\model\service\SocialActivityRefundService;
use app\social_activity\model\service\SocialActivityRefundLock;

function ensure($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
function fixture($table, array $values)
{
    $prefix = config('database.connections.mysql.prefix');
    foreach (Db::query('SHOW COLUMNS FROM `' . $prefix . $table . '`') as $column) {
        if (isset($values[$column['Field']]) || stripos($column['Extra'], 'auto_increment') !== false || $column['Null'] === 'YES' || $column['Default'] !== null) { continue; }
        $type = $column['Type'];
        $value = preg_match('/int|decimal|float|double|bit/', $type) ? 0 : '';
        if (preg_match('/datetime|timestamp/', $type)) { $value = date('Y-m-d H:i:s'); }
        elseif (preg_match('/^date/', $type)) { $value = date('Y-m-d'); }
        $values[$column['Field']] = $value;
    }
    return Db::name($table)->insertGetId($values);
}
class FakeRefundGateway
{
    public $mode = 'pending'; public $sends = 0; public $queries = 0;
    public function assertReady() {}
    public function verifyNotification(array $params) {}
    public function submit($row) {
        $this->sends++;
        if ($this->mode === 'timeout') { throw new RuntimeException('fixture transport timeout'); }
        if (in_array($this->mode, ['success', 'missing_trxid'], true)) {
            return ['trxstatus' => '0000', 'trxid' => $this->mode === 'success' ? '12345678901234567890' : '', 'reqsn' => $row['refund_no']];
        }
        if ($this->mode === 'unknown') { return ['reqsn' => $row['refund_no']]; }
        return ['trxstatus' => $this->mode === 'failure' ? '3008' : '2000', 'errmsg' => 'fixture failure', 'reqsn' => $row['refund_no']];
    }
    public function query($row) {
        $this->queries++;
        return ['trxstatus' => $this->mode === 'success' ? '0000' : ($this->mode === 'failure' ? '3008' : '2000'), 'errmsg' => 'fixture query failure', 'trxamt' => $row['online_amount'],
            'trxid' => '12345678901234567890', 'reqsn' => $row['refund_no'], 'fintime' => date('YmdHis')];
    }
}
class TestRefundService extends SocialActivityRefundService
{
    public $fake; public $queued = [];
    public function __construct() { $this->fake = new FakeRefundGateway(); }
    protected function gateway(array $pay) { return $this->fake; }
    protected function enqueue($refundId) { $this->queued[] = $refundId; }
}
function createOrder($uid, $online = 934, $balance = 300)
{
    $no = 'TEST' . bin2hex(random_bytes(8));
    $id = fixture('social_activity_order', ['uid' => $uid, 'order_sn' => $no, 'activity_id' => 17, 'session_id' => 1,
        'activity_name' => 'refund integration fixture', 'host_village_id' => 101, 'host_village_name' => 'fixture village',
        'paid' => 1, 'status' => 10, 'refund_status' => 0, 'is_sign' => 0, 'num' => 1,
        'amount' => ($online + $balance) / 100, 'money_online_pay' => $online / 100, 'money_system_balance' => $balance / 100,
        'pay_time' => date('Y-m-d H:i:s', time() - 600), 'ticket_start_time' => date('Y-m-d H:i:s', time() + 3600),
        'ticket_end_time' => date('Y-m-d H:i:s', time() + 7200), 'pay_orderno' => $no]);
    fixture('pay_order_info', ['business' => 'social_activity', 'business_order_id' => $id, 'orderid' => $no,
        'paid' => 1, 'paid_money' => $online, 'current_system_balance' => $balance, 'refund_money' => 0, 'refund_nums' => 0,
        'channel' => 'allinpay', 'rate' => 1, 'pay_config' => '{}', 'paid_time' => time() - 600]);
    return $id;
}
$redis = Cache::store('redis')->handler();
ensure($redis->ping() !== false, 'Redis ping');
$lockId = random_int(900000000, 999999999);
SocialActivityRefundLock::run($lockId, function () use ($lockId, $redis) {
    $blocked = false;
    try { SocialActivityRefundLock::run($lockId, function () {}); } catch (RuntimeException $e) { $blocked = true; }
    ensure($blocked, 'Redis prevents concurrent acquire');
    // 模拟原锁过期后其他请求获得锁，旧请求不得释放新锁。
    $redis->set('social-activity:refund:' . $lockId, 'replacement', ['ex' => 30]);
});
ensure($redis->get('social-activity:refund:' . $lockId) === 'replacement', 'token checked release');
$redis->del('social-activity:refund:' . $lockId);
$originalAutoQuery = config('social_activity_refund.auto_query_enabled', false);
Db::startTrans();
try {
    $uid = fixture('user', ['nickname' => 'refund-test', 'now_money' => '10.00']);
    ensure(!$originalAutoQuery, 'automatic fallback disabled by default');
    foreach (['pending', 'timeout', 'unknown', 'success'] as $mode) {
        $paused = new TestRefundService();
        $paused->fake->mode = $mode;
        $pausedOrder = createOrder($uid, 934, 0);
        $pausedResult = $paused->apply($pausedOrder, $uid);
        $paused->apply($pausedOrder, $uid);
        ensure(!$paused->queued && $paused->fake->queries === 0 && $paused->fake->sends === 1, $mode . ' no automatic query or queue when disabled');
        ensure($pausedResult['refund_status'] === ($mode === 'success' ? 2 : ($mode === 'timeout' ? 3 : 1)), $mode . ' correct user status with fallback disabled');
        // 排除这组测试记录，保持后续列表隔离断言的样本范围。
        Db::name('social_activity_refund')->where('id', $pausedResult['refund_id'])->update(['host_village_id' => 103]);
    }
    config(['auto_query_enabled' => true], 'social_activity_refund');
    $service = new TestRefundService();
    $order = createOrder($uid);
    $first = $service->apply($order, $uid);
    ensure($first['refund_status'] === 1 && $service->fake->sends === 1, 'submit processing');
    $again = $service->apply($order, $uid);
    ensure($again['refund_id'] === $first['refund_id'] && $service->fake->sends === 1, 'duplicate never resubmits');
    ensure(array_map('intval', $service->queued) === [(int)$first['refund_id']] && $service->fake->queries === 0, 'processing queues once without synchronous query');
    $signed = Db::name('social_activity_order')->where(['id' => $order, 'status' => 10, 'refund_status' => 0, 'is_sign' => 0])->update(['is_sign' => 1]);
    ensure($signed === 0, 'processing prevents check-in');
    ensure((int)Db::name('social_activity_order')->where('id', $order)->value('status') === 10, 'capacity held until success');
    $blocked = false;
    try { $service->apply($order, $uid + 1); } catch (RuntimeException $e) { $blocked = true; }
    ensure($blocked, 'another user cannot access even existing refund');
    $refundRow = Db::name('social_activity_refund')->where('id', $first['refund_id'])->find();
    $untrusted = ['cusorderid' => $refundRow['refund_no'], 'trxamt' => 934, 'trxstatus' => '0000'];
    $waiting = false;
    try { $service->notify($untrusted); } catch (RuntimeException $e) { $waiting = true; }
    ensure($waiting && $service->userStatus($order, $uid)['refund_status'] === 1, 'forged success notification cannot finalize refund');
    ensure((float)Db::name('user')->where('uid', $uid)->value('now_money') === 10.0, 'notification alone cannot credit balance');
    $queries = $service->fake->queries;
    try { $service->notify($untrusted); } catch (RuntimeException $e) {}
    ensure($service->fake->queries === $queries, 'notification query throttle');
    $service->fake->mode = 'success';
    $confirmed = $service->sync($first['refund_id']);
    if ((int)$confirmed['status'] !== 2) { echo 'Confirmation failure: ' . $confirmed['failure_reason'] . "\n"; }
    ensure($service->userStatus($order, $uid)['refund_status'] === 2, 'query finalizes success');
    ensure((float)Db::name('user')->where('uid', $uid)->value('now_money') === 13.0, 'balance refund exactly once');
    $service->sync($first['refund_id']);
    $service->apply($order, $uid);
    ensure((float)Db::name('user')->where('uid', $uid)->value('now_money') === 13.0 && $service->fake->sends === 1, 'repeat confirmation and apply idempotent');
    ensure((int)Db::name('social_activity_order')->where('id', $order)->value('status') === 20, 'tickets cancelled on success');
    foreach (['failure', 'timeout'] as $mode) {
        $queuedBefore = count($service->queued);
        $service->fake->mode = $mode;
        $id = createOrder($uid);
        $result = $service->apply($id, $uid);
        $row = Db::name('social_activity_refund')->where('id', $result['refund_id'])->find();
        ensure($result['refund_status'] === 3 && $row['failure_reason'] !== '', $mode . ' shows generic failure for user and stores reason');
        $sends = $service->fake->sends;
        $service->apply($id, $uid);
        ensure($service->fake->sends === $sends, $mode . ' never retried by user');
        ensure(count($service->queued) === $queuedBefore + ($mode === 'timeout' ? 1 : 0), $mode . ' queues only uncertain result');
    }
    $balanceOrder = createOrder($uid, 0, 500);
    $result = $service->apply($balanceOrder, $uid);
    ensure($result['refund_status'] === 2, 'balance-only refund succeeds without channel');
    ensure((float)Db::name('user')->where('uid', $uid)->value('now_money') === 18.0, 'balance-only credited');
    $visible = $service->getList([], ['id' => 1, 'status' => 1, 'role_id' => 9, 'village_id' => '102']);
    ensure($visible['total'] === 0, 'village scope prevents cross-village reads');
    $visible = $service->getList(['status' => '2', 'host_village_id' => 101], ['id' => 1, 'status' => 1, 'role_id' => 9, 'village_id' => '101']);
    ensure($visible['total'] === 2, 'status and village filters');
    $direct = new TestRefundService();
    $direct->fake->mode = 'success';
    $directOrder = createOrder($uid);
    $result = $direct->apply($directOrder, $uid);
    ensure($result['refund_status'] === 2 && $direct->fake->queries === 0 && !$direct->queued, 'immediate success finalizes without any query or queue');
    ensure((int)Db::name('social_activity_order')->where('id', $directOrder)->value('status') === 20, 'immediate success cancels tickets');
    $direct->apply($directOrder, $uid);
    $direct->sync($result['refund_id']);
    ensure($direct->fake->sends === 1 && $direct->fake->queries === 0 && !$direct->queued, 'immediate success remains idempotent');
    ensure((float)Db::name('user')->where('uid', $uid)->value('now_money') === 21.0, 'immediate success credits balance exactly once');
    foreach (['unknown', 'missing_trxid'] as $mode) {
        $fallback = new TestRefundService();
        $fallback->fake->mode = $mode;
        $id = createOrder($uid);
        $result = $fallback->apply($id, $uid);
        $fallback->apply($id, $uid);
        ensure($result['refund_status'] === ($mode === 'missing_trxid' ? 3 : 1) && array_map('intval', $fallback->queued) === [(int)$result['refund_id']] && $fallback->fake->sends === 1, $mode . ' queues once for reconciliation');
    }
    $adminService = new TestRefundService();
    $admin = ['id' => 1, 'status' => 1, 'role_id' => 9, 'village_id' => '101'];
    $adminOrder = createOrder($uid);
    $adminRefund = $adminService->apply($adminOrder, $uid);
    $denied = false;
    try { $adminService->updateByAdmin($adminRefund['refund_id'], array_merge($admin, ['village_id' => '102'])); }
    catch (RuntimeException $e) { $denied = true; }
    ensure($denied && $adminService->fake->queries === 0, 'admin updates enforce village scope before querying gateway');
    $adminService->fake->mode = 'failure';
    $failed = $adminService->updateByAdmin($adminRefund['refund_id'], $admin);
    ensure($failed['status'] === 3 && $failed['failure_reason'] === 'fixture query failure', 'admin query records confirmed failure and reason');
    ensure($adminService->userStatus($adminOrder, $uid)['refund_status'] === 3, 'confirmed failure displays generic failure for user');
    $adminService->fake->mode = 'success';
    $done = $adminService->updateByAdmin($adminRefund['refund_id'], $admin);
    ensure($done['status'] === 2 && $done['failure_reason'] === '', 'admin success finalizes and clears outdated error');
    $queryCount = $adminService->fake->queries;
    $adminService->updateByAdmin($adminRefund['refund_id'], $admin);
    ensure($adminService->fake->queries === $queryCount, 'admin repeated update never queries completed refund');
    $filtered = $adminService->getList(['order_status' => '20', 'refund_no' => Db::name('social_activity_refund')->where('id', $adminRefund['refund_id'])->value('refund_no')], $admin);
    ensure($filtered['total'] === 1 && $filtered['data'][0]['order_status_text'] === '已取消', 'order status filter and display');
    $filtered = $adminService->getList(['order_status' => '10', 'refund_no' => Db::name('social_activity_refund')->where('id', $adminRefund['refund_id'])->value('refund_no')], $admin);
    ensure($filtered['total'] === 0, 'order status filter excludes other states');
    $noTimeLimit = new TestRefundService();
    $noTimeLimit->fake->mode = 'success';
    $startedOrder = createOrder($uid, 934, 0);
    Db::name('social_activity_order')->where('id', $startedOrder)->update([
        'ticket_start_time' => date('Y-m-d H:i:s', time() - 7200),
        'ticket_end_time' => date('Y-m-d H:i:s', time() - 3600),
        'pay_time' => date('Y-m-d H:i:s')]);
    Db::name('pay_order_info')->where(['business' => 'social_activity', 'business_order_id' => $startedOrder])->update(['paid_time' => time()]);
    ensure($noTimeLimit->apply($startedOrder, $uid)['refund_status'] === 2, 'just-paid and ended session can refund if unused');
    $activity = fixture('social_activity', ['name' => 'refund-cancel-test', 'start_time' => date('Y-m-d H:i:s', time() + 3600), 'status' => 10]);
    $occupiedOrder = createOrder($uid);
    Db::name('social_activity_order')->where('id', $occupiedOrder)->update(['activity_id' => $activity]);
    $blocked = false;
    try { (new \app\social_activity\model\service\SocialActivityService())->cancelActivity(['id' => $activity, 'reason' => 'test', 'editUser' => ['id' => 1, 'realname' => 'test']]); }
    catch (RuntimeException $e) { $blocked = true; }
    ensure($blocked && (int)Db::name('social_activity')->where('id', $activity)->value('status') === 10, 'occupied activity cannot be cancelled');
    echo "PASS: Redis NX, token release, database idempotency, owner isolation, check-in block, pending capacity, success reconciliation, balance ledger, failure/timeout, list scope\n";
} finally {
    config(['auto_query_enabled' => $originalAutoQuery], 'social_activity_refund');
    Db::rollback();
    echo "All fixture database changes rolled back. No gateway calls or queue tasks were made.\n";
}
