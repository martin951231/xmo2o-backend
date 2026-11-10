-- Independent fixed registration forms. Existing visitor tables are untouched.
CREATE TABLE IF NOT EXISTS `pigcms_visitor_register_config` (
  `village_id` int unsigned NOT NULL,
  `appid` varchar(32) NOT NULL DEFAULT '',
  `secret_encrypted` text NOT NULL,
  `version` int unsigned NOT NULL DEFAULT 1,
  `update_time` int unsigned NOT NULL,
  PRIMARY KEY (`village_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `pigcms_visitor_register_form` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `village_id` int unsigned NOT NULL,
  `form_type` varchar(16) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'draft',
  `qrcode_path` varchar(255) NOT NULL DEFAULT '',
  `qrcode_appid` varchar(32) NOT NULL DEFAULT '',
  `qrcode_generated_at` int unsigned NOT NULL DEFAULT 0,
  `published_at` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`), UNIQUE KEY `village_form` (`village_id`,`form_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `pigcms_visitor_register_person` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `village_id` int unsigned NOT NULL,
  `registration_no` varchar(40) NOT NULL,
  `request_id` varchar(64) NOT NULL,
  `payload_hash` char(64) NOT NULL,
  `visit_time` datetime NOT NULL,
  `visitor_name` varchar(60) NOT NULL,
  `organization_or_id` varchar(120) NOT NULL,
  `phone` varchar(11) NOT NULL,
  `visit_reason` varchar(255) NOT NULL,
  `host_name_room` varchar(120) NOT NULL,
  `remark` varchar(500) NOT NULL DEFAULT '',
  `create_time` int unsigned NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `registration_no` (`registration_no`),
  UNIQUE KEY `idempotency` (`village_id`,`request_id`),
  KEY `visit_time` (`village_id`,`visit_time`), KEY `phone` (`village_id`,`phone`),
  KEY `recent_duplicate` (`village_id`,`payload_hash`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `pigcms_visitor_register_vehicle` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `village_id` int unsigned NOT NULL,
  `registration_no` varchar(40) NOT NULL,
  `request_id` varchar(64) NOT NULL,
  `payload_hash` char(64) NOT NULL,
  `plate_number` varchar(16) NOT NULL,
  `entry_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `destination` varchar(120) NOT NULL,
  `phone` varchar(11) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'entered',
  `create_time` int unsigned NOT NULL,
  `update_time` int unsigned NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `registration_no` (`registration_no`),
  UNIQUE KEY `idempotency` (`village_id`,`request_id`),
  KEY `entry_time` (`village_id`,`entry_time`), KEY `phone` (`village_id`,`phone`),
  KEY `vehicle_status` (`village_id`,`status`,`entry_time`),
  KEY `recent_duplicate` (`village_id`,`payload_hash`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Atomic per-IP rate limits, also available when Redis is unavailable.
CREATE TABLE IF NOT EXISTS `pigcms_visitor_register_rate` (
  `rate_key` char(64) NOT NULL,
  `hits` int unsigned NOT NULL DEFAULT 0,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`rate_key`), KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO `pigcms_visitor_register_form` (`village_id`,`form_type`)
SELECT `village_id`,'visitor' FROM `pigcms_house_village` WHERE `village_id` > 0;
INSERT IGNORE INTO `pigcms_visitor_register_form` (`village_id`,`form_type`)
SELECT `village_id`,'vehicle' FROM `pigcms_house_village` WHERE `village_id` > 0;
INSERT INTO `pigcms_house_menu_new`
 (`fid`,`name`,`module`,`action`,`select_module`,`select_action`,`choose_page`,`icon`,`sort`,`show`,`status`,`type`,`jump_type`,`jump_url`,`application_id`,`application_txt`,`component`,`path`,`is_hide`)
SELECT 194,'访客登记(新)','','','','','','',100,1,1,1,3,'',0,'','visitorRegisterNew','/village/village.visitorRegisterNew/index',0
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `pigcms_house_menu_new` WHERE `component`='visitorRegisterNew');
