<?php
namespace app\social_activity\model\service;

use think\facade\Cache;

class SocialActivityRefundLock
{
    public static function run($orderId, callable $action)
    {
        // 直接使用 Redis 的原子 SET NX EX，不使用 get/set 组合模拟锁。
        $redis = Cache::store('redis')->handler();
        $key = 'social-activity:refund:' . (int)$orderId;
        $token = bin2hex(random_bytes(16));
        $acquired = $redis instanceof \Redis ? $redis->set($key, $token, ['nx', 'ex' => 120]) : $redis->set($key, $token, 'EX', 120, 'NX');
        if (!$acquired) {
            throw new \RuntimeException('退款正在处理，请稍后查看状态');
        }
        try {
            return $action();
        } finally {
            // 仅释放自己持有的锁；Redis 故障时依靠 TTL，不能删除其他请求的锁。
            try {
                $lua = 'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end';
                if ($redis instanceof \Redis) { $redis->eval($lua, [$key, $token], 1); }
                else { $redis->eval($lua, 1, $key, $token); }
            } catch (\Throwable $e) {
                error_log('Social activity refund lock release failed');
            }
        }
    }
}
