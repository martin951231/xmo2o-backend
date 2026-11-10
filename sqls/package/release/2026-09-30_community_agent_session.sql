-- Apply explicitly before enabling community_agent. No existing table is altered.
-- Replace pigcms_ if this installation uses another prefix.
CREATE TABLE IF NOT EXISTS `pigcms_community_agent_session` (
  `id` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `uid` bigint unsigned NOT NULL,
  `village_id` bigint unsigned NOT NULL,
  `bind_id` bigint unsigned NOT NULL,
  `state` varchar(24) NOT NULL,
  `suggested_intent` varchar(32) NOT NULL DEFAULT 'unknown',
  `intent` varchar(32) NOT NULL DEFAULT '',
  `payload` text NOT NULL,
  `version` int unsigned NOT NULL DEFAULT 1,
  `application_id` bigint unsigned NOT NULL DEFAULT 0,
  `created_at` int unsigned NOT NULL,
  `updated_at` int unsigned NOT NULL,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_created` (`uid`, `created_at`),
  KEY `expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
