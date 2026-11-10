<?php
// Run once before deploying the participant-aware API. Safe to run repeatedly.
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new \think\App(dirname(__DIR__) . DIRECTORY_SEPARATOR);
$app->initialize();
$prefix = config('database.connections.' . config('database.default') . '.prefix');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) { throw new RuntimeException('数据库前缀不合法'); }
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `{$prefix}social_activity_participant` (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL, activity_id INT UNSIGNED NOT NULL, session_id INT UNSIGNED NOT NULL,
 bind_id INT UNSIGNED NOT NULL, uid INT UNSIGNED NOT NULL DEFAULT 0,
 phone VARCHAR(20) NOT NULL DEFAULT '',
 user_name VARCHAR(100) NOT NULL DEFAULT '', id_number VARCHAR(32) NOT NULL DEFAULT '',
 role VARCHAR(30) NOT NULL DEFAULT '', user_source VARCHAR(255) NOT NULL DEFAULT '',
 is_sign TINYINT UNSIGNED NOT NULL DEFAULT 0, sign_time DATETIME NULL,
 KEY ix_order_member (order_id,bind_id),
 UNIQUE KEY uq_order_identity (order_id,id_number),
 KEY ix_member_phone (phone,uid),
 KEY ix_member_uid (uid,order_id), KEY ix_member_bind (bind_id,order_id),
 KEY ix_member_id (id_number,order_id), KEY ix_activity (activity_id,session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Upgrade the previous bound-members-only table without changing existing registrations.
$table = $prefix . 'social_activity_participant';
$columns = array_column(\think\facade\Db::query("SHOW COLUMNS FROM `$table`"), 'Field');
if (!in_array('phone', $columns, true)) { \think\facade\Db::execute("ALTER TABLE `$table` ADD COLUMN phone VARCHAR(20) NOT NULL DEFAULT ''"); }
$indexes = array_column(\think\facade\Db::query("SHOW INDEX FROM `$table`"), 'Key_name');
if (in_array('uq_order_member', $indexes, true)) { \think\facade\Db::execute("ALTER TABLE `$table` DROP INDEX uq_order_member, ADD KEY ix_order_member (order_id,bind_id)"); }
if (!in_array('uq_order_identity', $indexes, true)) { \think\facade\Db::execute("ALTER TABLE `$table` ADD UNIQUE KEY uq_order_identity (order_id,id_number)"); }
if (!in_array('ix_member_phone', $indexes, true)) { \think\facade\Db::execute("ALTER TABLE `$table` ADD KEY ix_member_phone (phone,uid)"); }
echo "Activity participant schema installed.\n";
