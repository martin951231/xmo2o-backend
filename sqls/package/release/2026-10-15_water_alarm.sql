-- Demo has one physical endpoint, globally unique to prevent cross-village aliases.
CREATE TABLE IF NOT EXISTS `pigcms_water_alarm_device` (
 `id` int unsigned NOT NULL AUTO_INCREMENT,
 `village_id` int unsigned NOT NULL,
 `name` varchar(60) NOT NULL,
 `endpoint` varchar(80) NOT NULL,
 `volume` smallint unsigned NOT NULL DEFAULT 1,
 `state_json` text NULL,
 `last_seen` int unsigned NOT NULL DEFAULT 0,
 `last_error` varchar(255) NOT NULL DEFAULT '',
 `create_time` int unsigned NOT NULL,
 `update_time` int unsigned NOT NULL,
 PRIMARY KEY (`id`), UNIQUE KEY `endpoint` (`endpoint`), KEY `village_id` (`village_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `pigcms_water_alarm_log` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `village_id` int unsigned NOT NULL,
 `device_id` int unsigned NOT NULL,
 `operator_id` int unsigned NOT NULL,
 `operator_role` int unsigned NOT NULL,
 `action` varchar(32) NOT NULL,
 `result` varchar(16) NOT NULL,
 `message` varchar(255) NOT NULL DEFAULT '',
 `create_time` int unsigned NOT NULL,
 PRIMARY KEY (`id`), KEY `device_logs` (`village_id`,`device_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `pigcms_house_menu_new`
 (`fid`,`name`,`module`,`action`,`select_module`,`select_action`,`choose_page`,`icon`,`sort`,`show`,`status`,`type`,`jump_type`,`jump_url`,`application_id`,`application_txt`,`component`,`path`,`is_hide`)
SELECT parent.id,'水浸管理','','','','','','',100,1,1,1,3,'',0,'','waterAlarm','/village/village.waterAlarm/index',0
FROM (SELECT id FROM `pigcms_house_menu_new` WHERE name='智能硬件' AND fid=0 AND status=1 ORDER BY id LIMIT 1) parent
WHERE NOT EXISTS (SELECT 1 FROM `pigcms_house_menu_new` WHERE component='waterAlarm');
