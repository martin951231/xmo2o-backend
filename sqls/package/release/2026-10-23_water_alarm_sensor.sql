CREATE TABLE IF NOT EXISTS `pigcms_water_alarm_sensor` (
 `id` int unsigned NOT NULL AUTO_INCREMENT,
 `village_id` int unsigned NOT NULL,
 `sensor_id` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 `alarm_text` varchar(199) NOT NULL DEFAULT '',
 `enabled` tinyint unsigned NOT NULL DEFAULT 0,
 `last_state` tinyint NOT NULL DEFAULT -1,
 `last_seen` int unsigned NOT NULL DEFAULT 0,
 `create_time` int unsigned NOT NULL,
 PRIMARY KEY (`id`), UNIQUE KEY `sensor_id` (`sensor_id`), KEY `village` (`village_id`,`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `pigcms_water_alarm_linkage` (
 `village_id` int unsigned NOT NULL,
 `signature` varchar(64) NOT NULL DEFAULT '',
 PRIMARY KEY (`village_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
