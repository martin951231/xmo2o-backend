-- Execute after 2026-10-20_visitor_register_identity.sql.
CREATE TABLE IF NOT EXISTS `pigcms_visitor_register_record_profile` (
  `registration_no` varchar(40) NOT NULL,
  `village_id` int unsigned NOT NULL,
  `nickname` varchar(60) NOT NULL,
  PRIMARY KEY (`registration_no`),
  KEY `village_id` (`village_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Only existing identity-linked records have a trustworthy nickname.
INSERT IGNORE INTO `pigcms_visitor_register_record_profile` (`registration_no`,`village_id`,`nickname`)
SELECT `registration_no`,`village_id`,`nickname` FROM `pigcms_visitor_register_identity`;
