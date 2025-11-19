<?php
ini_set('display_errors', 'on');
use Workerman\Worker;
//// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

// 标记是全局启动
define('GLOBAL_START', 1);

require_once  '../../vendor/autoload.php';

// 加载所有extend/socket/start*.php，以便启动所有服务
foreach(glob(dirname(dirname(__DIR__)).'/extend/socketdoor/start*.php') as $start_file)
{
    require_once $start_file;
}
// 运行所有服务
Worker::runAll();