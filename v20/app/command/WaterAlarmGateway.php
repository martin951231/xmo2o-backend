<?php
namespace app\command;

use app\community\model\service\WaterAlarmGatewayServer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class WaterAlarmGateway extends Command
{
    protected function configure()
    {
        $this->setName('waterAlarmGateway')->setDescription('接收声光报警器TCP CLIENT连接并提供本机控制服务');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $config = config('water_alarm');
            if ($config['mode'] !== 'gateway') { throw new \RuntimeException('请先设置 WATER_ALARM.MODE=gateway'); }
            $server = new WaterAlarmGatewayServer($config, function ($line) use ($output) { $output->writeln($line); });
            if (function_exists('pcntl_async_signals')) {
                pcntl_async_signals(true);
                pcntl_signal(SIGTERM, function () use ($server) { $server->stop(); });
                pcntl_signal(SIGINT, function () use ($server) { $server->stop(); });
            }
            $server->run();
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('TCP接入服务启动/运行失败：' . $e->getMessage());
            return 1;
        }
    }
}
