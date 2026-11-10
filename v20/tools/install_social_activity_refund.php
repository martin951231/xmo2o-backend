<?php
// 使用与 Web 相同的 PHP/数据库配置执行：php tools/install_social_activity_refund.php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__) . DIRECTORY_SEPARATOR);
$app->initialize();
use think\facade\Db;

$prefix = config('database.connections.' . config('database.default') . '.prefix');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) { throw new RuntimeException('数据库前缀不合法'); }
$table = $prefix . 'social_activity_refund';
Db::execute("CREATE TABLE IF NOT EXISTS `$table` (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL, uid INT UNSIGNED NOT NULL,
 order_sn VARCHAR(50) NOT NULL, pay_order_id BIGINT UNSIGNED NOT NULL,
 pay_order_no VARCHAR(50) NOT NULL, refund_no VARCHAR(32) NOT NULL,
 original_trxid VARCHAR(50) NOT NULL DEFAULT '', refund_trxid VARCHAR(50) NOT NULL DEFAULT '',
 activity_id INT UNSIGNED NOT NULL, activity_name VARCHAR(255) NOT NULL,
 host_village_id INT UNSIGNED NOT NULL DEFAULT 0, host_village_name VARCHAR(255) NOT NULL DEFAULT '',
 refund_amount BIGINT UNSIGNED NOT NULL, paid_amount BIGINT UNSIGNED NOT NULL,
 online_amount BIGINT UNSIGNED NOT NULL, balance_amount BIGINT UNSIGNED NOT NULL,
 status TINYINT UNSIGNED NOT NULL DEFAULT 0,
 reason VARCHAR(255) NOT NULL DEFAULT '', source VARCHAR(30) NOT NULL DEFAULT 'user',
 failure_reason VARCHAR(500) NOT NULL DEFAULT '', gateway_code VARCHAR(20) NOT NULL DEFAULT '',
 created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 submitted_at DATETIME NULL, finished_at DATETIME NULL, last_query_at DATETIME NULL,
 last_query_admin_id INT UNSIGNED NOT NULL DEFAULT 0,
 UNIQUE KEY uq_refund_order (order_id), UNIQUE KEY uq_refund_no (refund_no),
 KEY ix_refund_scope (host_village_id,status,created_at), KEY ix_refund_pending (status,last_query_at),
 KEY ix_refund_pay (pay_order_no), KEY ix_refund_activity (activity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$orderTable = $prefix . 'social_activity_order';
$columns = array_column(Db::query("SHOW COLUMNS FROM `$orderTable`"), 'Field');
foreach (['refund_status' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0', 'host_village_id' => 'INT UNSIGNED NOT NULL DEFAULT 0', 'host_village_name' => "VARCHAR(255) NOT NULL DEFAULT ''"] as $column => $definition) {
    if (!in_array($column, $columns, true)) { Db::execute("ALTER TABLE `$orderTable` ADD COLUMN `$column` $definition"); }
}
// 历史订单没有主办小区快照，仅补报表归属；退款商户始终从原支付单读取。
Db::execute("UPDATE `$orderTable` o INNER JOIN `{$prefix}social_activity` a ON a.id=o.activity_id
 LEFT JOIN `{$prefix}house_village` v ON v.village_id=a.host_village_id
 SET o.host_village_id=a.host_village_id,o.host_village_name=COALESCE(v.village_name,'')
 WHERE o.host_village_id=0 AND a.host_village_id>0");
Db::transaction(function () {
    $sibling = Db::name('social_activity_menu')->where('component', 'RegistrationForm')->find();
    if (!$sibling) { throw new RuntimeException('报名表单菜单不存在，请核对后台菜单配置'); }
    if (!Db::name('social_activity_menu')->where('component', 'SocialActivityRefundList')->find()) {
        Db::name('social_activity_menu')->insert(['pid' => $sibling['pid'], 'name' => 'SocialActivityRefundList',
            'title' => '退款列表', 'component' => 'SocialActivityRefundList', 'path' => '/socialactivity/socialActivity/refundList',
            'perms' => '', 'icon' => 'iconzhifu', 'type' => 2, 'sort' => (int)$sibling['sort'] + 1, 'status' => 1]);
    }
    Db::name('social_activity_menu')->where('component', 'SocialActivityRefundList')->whereIn('icon', ['', 'iconzhangdan-copy', 'icondingdan3'])->update(['icon' => 'iconzhifu']);
});
echo "Refund schema and menu installed. Assign the new menu to permitted non-superadmin roles.\n";
