<?php
namespace app\job;

use think\queue\Job;
use think\facade\Db;
use app\social_activity\model\service\SocialActivityRefundService;

class SocialActivityRefundQueryJob
{
    public function fire(Job $job, $data)
    {
        // 停用时也清理已投递的旧任务，退款记录保留供管理员查询。
        if (!config('social_activity_refund.auto_query_enabled', false)) { $job->delete(); return; }
        $row = Db::name('social_activity_refund')->where('id', (int)$data['id'])->find();
        if (!$row || in_array((int)$row['status'], [2, 3, 4], true)) { $job->delete(); return; }
        try {
            $row = (new SocialActivityRefundService())->sync($row['id']);
            if (in_array((int)$row['status'], [2, 3, 4], true)) { $job->delete(); return; }
        } catch (\Throwable $e) {
            error_log('Social activity refund query: ' . $e->getMessage());
        }
        if (time() - strtotime($row['created_at']) > 86400) {
            Db::name('social_activity_refund')->where('id', $row['id'])->whereIn('status', [0, 1])->update(['status' => 4,
                'failure_reason' => '退款超过24小时未确认，请管理员查询核对', 'updated_at' => date('Y-m-d H:i:s')]);
            $job->delete();
        } else {
            $job->release(60);
        }
    }
}
