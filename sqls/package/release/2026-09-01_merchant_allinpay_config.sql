CREATE TABLE IF NOT EXISTS `pigcms_merchant_allinpay_config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `mer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商家ID',
    `allinpay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启：1开启，0关闭',
    `allinpay_appid` varchar(100) NOT NULL DEFAULT '' COMMENT '通联AppID',
    `allinpay_cusid` varchar(100) NOT NULL DEFAULT '' COMMENT '通联商户号',
    `pay_allinpay_private_key` text NOT NULL COMMENT '通联私钥',
    `add_time` int(10) unsigned NOT NULL DEFAULT '0',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_mer_id` (`mer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商家通联支付配置';

-- 若旧平台配置已投入使用，首次升级时将它复制给每个现有商家，随后由各商家独立维护。
INSERT INTO `pigcms_merchant_allinpay_config`
    (`mer_id`, `allinpay_status`, `allinpay_appid`, `allinpay_cusid`, `pay_allinpay_private_key`, `add_time`, `update_time`)
SELECT
    `mer_id`,
    IFNULL((SELECT `value` FROM `pigcms_config` WHERE `name` = 'allinpay_status' LIMIT 1), '0'),
    IFNULL((SELECT `value` FROM `pigcms_config` WHERE `name` = 'allinpay_appid' LIMIT 1), ''),
    IFNULL((SELECT `value` FROM `pigcms_config` WHERE `name` = 'allinpay_cusid' LIMIT 1), ''),
    IFNULL((SELECT `value` FROM `pigcms_config` WHERE `name` = 'pay_allinpay_private_key' LIMIT 1), ''),
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM `pigcms_merchant`
ON DUPLICATE KEY UPDATE `mer_id` = VALUES(`mer_id`);

DELETE FROM `pigcms_config`
WHERE `name` IN ('allinpay_status', 'allinpay_appid', 'allinpay_cusid', 'pay_allinpay_private_key');
