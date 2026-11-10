CREATE TABLE IF NOT EXISTS `pigcms_merchant_store_allinpay_config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '店铺ID',
    `mer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属商家ID',
    `allinpay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启：1开启，0关闭',
    `allinpay_appid` varchar(100) NOT NULL DEFAULT '' COMMENT '通联AppID',
    `allinpay_cusid` varchar(100) NOT NULL DEFAULT '' COMMENT '通联商户号',
    `pay_allinpay_private_key` text NOT NULL COMMENT '通联私钥',
    `add_time` int(10) unsigned NOT NULL DEFAULT '0',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_store_id` (`store_id`),
    KEY `idx_mer_id` (`mer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='店铺通联支付配置';

-- 将原商家级配置复制给该商家名下的每个店铺，之后各店铺独立维护。
INSERT INTO `pigcms_merchant_store_allinpay_config`
    (`store_id`, `mer_id`, `allinpay_status`, `allinpay_appid`, `allinpay_cusid`, `pay_allinpay_private_key`, `add_time`, `update_time`)
SELECT
    store.`store_id`,
    store.`mer_id`,
    IFNULL(config.`allinpay_status`, 0),
    IFNULL(config.`allinpay_appid`, ''),
    IFNULL(config.`allinpay_cusid`, ''),
    IFNULL(config.`pay_allinpay_private_key`, ''),
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM `pigcms_merchant_store` AS store
LEFT JOIN `pigcms_merchant_allinpay_config` AS config ON config.`mer_id` = store.`mer_id`
ON DUPLICATE KEY UPDATE
    `mer_id` = VALUES(`mer_id`);

DROP TABLE IF EXISTS `pigcms_merchant_allinpay_config`;
