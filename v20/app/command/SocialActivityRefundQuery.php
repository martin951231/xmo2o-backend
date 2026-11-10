<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use app\social_activity\model\service\SocialActivityRefundService;

class SocialActivityRefundQuery extends Command
{
    protected function configure()
    {
        $this->setName('socialActivityRefundQuery')->setDescription('查询待确认的社群退款；不会重发退款申请');
    }
    protected function execute(Input $input, Output $output)
    {
        $rows = Db::name('social_activity_refund')->whereIn('status', [0, 1])->order('last_query_at asc,id asc')->limit(100)->select();
        foreach ($rows as $row) {
            try {
                $result = (new SocialActivityRefundService())->sync($row['id']);
                $output->writeln($row['id'] . ': ' . SocialActivityRefundService::LABELS[$result['status']]);
            } catch (\Throwable $e) { $output->writeln($row['id'] . ': ' . $e->getMessage()); }
        }
    }
}
