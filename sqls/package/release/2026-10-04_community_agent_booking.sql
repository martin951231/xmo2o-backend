-- CMS and v20 must use the same database. Replace the prefix if necessary.
-- Keeps an immutable receipt for remote retries; do not purge while sessions can be queried.
CREATE TABLE IF NOT EXISTS `pigcms_community_agent_booking_receipt` (
  `session_id` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `uid` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
