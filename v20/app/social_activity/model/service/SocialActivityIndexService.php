<?php


namespace app\social_activity\model\service;

use app\social_activity\model\db\SocialActivity;
use app\social_activity\model\db\SocialActivityComment;
use app\social_activity\model\db\SocialActivityOrder;
use app\social_activity\model\db\SocialActivityViews;
use think\facade\Db;

class SocialActivityIndexService
{
    public function getInfo()
    {
        //活动数据
        $activity_statistics = (new SocialActivity())->field('status,count(*) as total')->group('status')->column('status,count(*) as total', 'status');
        $total = $activity_statistics ? array_sum(array_column($activity_statistics, 'total')) : 0;
        $pending = !empty($activity_statistics[SocialActivity::STATUS_0]) ? $activity_statistics[SocialActivity::STATUS_0]['total'] : 0;
        $published = (new SocialActivity())->where([['is_audit', '=', SocialActivity::STATUS_10]])->count(); //已发布活动包含已取消的活动
        $ended = (new SocialActivity())->where([['end_time', '<', date('Y-m-d 23:59:59', strtotime('-1 day'))], ['is_audit', '=', SocialActivity::STATUS_10],['status', '<>', SocialActivity::STATUS_30]])->count();

        //统计报名和签到数
        $order_statistics = (new SocialActivityOrder())->field('is_sign,count(*) as total')->where([
            ['status', 'in', [SocialActivityOrder::STATUS_10, SocialActivityOrder::STATUS_20]],
            ['paid', '=', 1],
        ])->group('is_sign')->column('is_sign,count(*) as total', 'is_sign');

        $total_attendance = $order_statistics ? array_sum(array_column($order_statistics, 'total')) : 0;
        $total_signups = !empty($order_statistics[1]) ? $order_statistics[1]['total'] : 0;

        //统计活动参与率
        $avg_participation_rate = $total_attendance ? (int)round($total_signups / $total_attendance * 100) : 0;

        //统计活动转换率
        $activity_user = (new SocialActivityOrder())->where([
            ['paid', '=', 1],
        ])->group('uid,activity_id,session_id')->count(); //总报名人数
        $total_views = (new SocialActivityViews())->group('uid,activity_id')->count(); //总浏览量
        $avg_conversion_rate = $total_views ? (int)round($activity_user / $total_views * 100) : 0;


        //评论覆盖
        $user_comment = (new SocialActivityComment())->where([
            ['audit_status', '=', SocialActivityComment::STATUS_10],
        ])->group('uid,activity_id')->count(); //总评论人数
        $comment_rate = $total_signups ? (int)round($user_comment / $total_signups * 100) : 0;

        //重复参与率
        $user_participation = (new SocialActivityOrder())->field('uid,count(*) as participation_count')->where([
            ['paid', '=', 1],
            ['is_sign', '=', 1],
        ])->group('uid')->having('participation_count >= 2')->count(); //重复参与人数
        $reengagement_rate = $total_signups ? (int)round($user_participation / $total_signups * 100) : 0;

        $data = [
            'activity_data' => [
                'total' => $total, //总活动数
                'pending' => $pending, //待审核
                'published' => $published, //已发布
                'ended' => $ended //已结束
            ],
            'participant_data' => [
                'total_signups' => $total_signups, //总签到数
                'total_attendance' => $total_attendance, //总报名数
            ],
            'effect_data' => [
                'avg_participation_rate' => $avg_participation_rate, //平均参与率
                'avg_conversion_rate' => $avg_conversion_rate, //平均转换率
                'comment_rate' => $comment_rate, //评论率
                'reengagement_rate' => $reengagement_rate, //重新参与率
            ]
        ];
        return $data;
    }
}
