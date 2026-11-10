-- Run before deploying the WeChat identity / same-day prefill update.
-- No modification to historic registration rows; they cannot be matched safely to a WeChat user.
CREATE TABLE IF NOT EXISTS `pigcms_visitor_register_identity` (
  `village_id` int unsigned NOT NULL,
  `form_type` varchar(16) NOT NULL,
  `subject_hash` char(64) NOT NULL,
  `record_date` date NOT NULL,
  `registration_no` varchar(40) NOT NULL,
  `nickname` varchar(60) NOT NULL,
  `verified_phone` varchar(11) NOT NULL,
  PRIMARY KEY (`village_id`,`form_type`,`subject_hash`,`record_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
