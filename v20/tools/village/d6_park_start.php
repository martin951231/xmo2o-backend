<?php
ini_set('display_errors', 'on');
use Workerman\Worker;

$database_config = include '../../../conf/db.php';
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

$info = (new Workerman\MySQL\Connection($database_config['DB_HOST'],$database_config['DB_PORT'],$database_config['DB_USER'],$database_config['DB_PWD'], $database_config['DB_NAME']))->query('select name,value from '.$database_config['DB_PREFIX'].'config where name="is_d6park_switch" and value = 1 ');
if(empty($info)){
    exit("Please turn on D6 intelligent parking switch first");
}

// 加载所有extend/socket/start*.php，以便启动所有服务
foreach(glob(dirname(dirname(__DIR__)).'/extend/socket/start*.php') as $start_file)
{
    require_once $start_file;
}
// 运行所有服务
Worker::runAll();