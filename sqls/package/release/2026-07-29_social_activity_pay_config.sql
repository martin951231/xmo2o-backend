CREATE TABLE IF NOT EXISTS `pigcms_social_activity_pay_config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `village_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '小区ID',
    `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '开启状态：0关闭，1开启',
    `app_id` varchar(100) NOT NULL DEFAULT '' COMMENT '通联支付AppID',
    `merchant_no` varchar(100) NOT NULL DEFAULT '' COMMENT '通联支付商户号',
    `private_key` text NOT NULL COMMENT '通联私钥',
    `create_time` datetime DEFAULT NULL COMMENT '创建时间',
    `update_time` datetime DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_village_id` (`village_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='社群活动支付配置';

ALTER TABLE `pigcms_social_activity`
    ADD COLUMN `host_village_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动主办社区ID' AFTER `category_id`,
    ADD KEY `idx_host_village_id` (`host_village_id`);

UPDATE `pigcms_social_activity` AS `activity`
INNER JOIN (
    SELECT `activity_id`, MIN(`id`) AS `first_session_id`
    FROM `pigcms_social_activity_session`
    GROUP BY `activity_id`
) AS `first_session`
    ON `first_session`.`activity_id` = `activity`.`id`
INNER JOIN `pigcms_social_activity_session` AS `session`
    ON `session`.`id` = `first_session`.`first_session_id`
SET `activity`.`host_village_id` = CAST(SUBSTRING_INDEX(`session`.`village_id`, ',', 1) AS UNSIGNED)
WHERE `activity`.`host_village_id` = 0
  AND `session`.`village_id` <> '';

INSERT INTO `pigcms_social_activity_menu`
    (`pid`, `name`, `title`, `icon`, `type`, `component`, `path`, `perms`, `sort`, `status`, `create_time`)
SELECT
    `id`,
    'PaymentSettings',
    '支付设置',
    'iconasset-management-the-asset-summary',
    2,
    'PaymentSettings',
    '/socialactivity/socialActivity/paymentSettings',
    '',
    850,
    1,
    NOW()
FROM `pigcms_social_activity_menu`
WHERE `name` = 'BasicSettings'
  AND NOT EXISTS (
      SELECT 1
      FROM (
          SELECT `name` FROM `pigcms_social_activity_menu`
      ) AS `existing_menu`
      WHERE `existing_menu`.`name` = 'PaymentSettings'
  )
LIMIT 1;

UPDATE `pigcms_social_activity_menu`
SET `icon` = 'iconasset-management-the-asset-summary',
    `update_time` = NOW()
WHERE `name` = 'PaymentSettings';

INSERT INTO `pigcms_social_activity_menu`
    (`pid`, `name`, `title`, `icon`, `type`, `component`, `path`, `perms`, `sort`, `status`, `create_time`)
SELECT
    `id`,
    '',
    '保存',
    '',
    3,
    NULL,
    '',
    'save',
    0,
    1,
    NOW()
FROM `pigcms_social_activity_menu`
WHERE `name` = 'PaymentSettings'
  AND NOT EXISTS (
      SELECT 1
      FROM (
          SELECT `pid`, `perms` FROM `pigcms_social_activity_menu`
      ) AS `existing_permission`
      WHERE `existing_permission`.`pid` = (
          SELECT `id`
          FROM (
              SELECT `id`, `name` FROM `pigcms_social_activity_menu`
          ) AS `payment_menu`
          WHERE `payment_menu`.`name` = 'PaymentSettings'
          LIMIT 1
      )
        AND `existing_permission`.`perms` = 'save'
  )
LIMIT 1;
