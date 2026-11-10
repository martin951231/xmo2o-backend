<?php
// CLI only; idempotent migration, no hardware commands or automatic device creation.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__) . '/');
$app->initialize();
try {
    $parent = \think\facade\Db::name('house_menu_new')->where(['name' => '智能硬件', 'fid' => 0, 'status' => 1])->find();
    if (!$parent) { throw new RuntimeException('未找到智能硬件父菜单，请先确认当前数据库为小区后台数据库'); }
    $sql = file_get_contents(dirname(__DIR__, 2) . '/sqls/package/release/2026-10-15_water_alarm.sql');
    $sql .= "\n" . file_get_contents(dirname(__DIR__, 2) . '/sqls/package/release/2026-10-15_water_alarm_tts.sql');
    $prefix = config('database.connections.mysql.prefix');
    $sql = str_replace('`pigcms_', '`' . $prefix, $sql);
    $sql = preg_replace('/^--.*$/m', '', $sql);
    foreach (explode(';', $sql) as $statement) {
        if (trim($statement) !== '') { \think\facade\Db::execute(trim($statement)); }
    }
    $menu = \think\facade\Db::name('house_menu_new')->where('component', 'waterAlarm')->find();
    if (!$menu) { throw new RuntimeException('菜单安装未完成'); }
    echo '水浸管理已安装，菜单ID：' . $menu['id'] . '。请重新登录后台，给普通管理员分配该菜单权限。' . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, '安装失败：' . $e->getMessage() . PHP_EOL);
    exit(1);
}
