-- TTS text library; existing installations only need this incremental migration.
CREATE TABLE IF NOT EXISTS `pigcms_water_alarm_text` (
 `id` int unsigned NOT NULL AUTO_INCREMENT,
 `village_id` int unsigned NOT NULL,
 `device_id` int unsigned NOT NULL,
 `content` varchar(199) NOT NULL,
 `create_time` int unsigned NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `device_content` (`device_id`,`content`),
 KEY `device_list` (`village_id`,`device_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
