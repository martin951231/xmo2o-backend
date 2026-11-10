-- 公共收益模块：科目、季度数据、明细与操作日志
CREATE TABLE IF NOT EXISTS `pigcms_public_revenue_subject` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `village_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '小区ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1收入 2支出',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '项目名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序值',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `is_del` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_village_type_name_del` (`village_id`,`type`,`name`,`is_del`),
  KEY `idx_village_type_status` (`village_id`,`type`,`status`,`is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公共收益收支项目';

CREATE TABLE IF NOT EXISTS `pigcms_public_revenue_period` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `village_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '小区ID',
  `year` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '年度',
  `quarter` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0年初结余 1-4季度',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0草稿 1已生效',
  `source_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1手工 2自动结转',
  `opening_accrual_balance` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '应收口径期初结余（元）',
  `opening_actual_balance` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '实收口径期初结余（元）',
  `accrual_income_total` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '应收合计（元）',
  `actual_income_total` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '实收合计（元）',
  `accrual_expense_total` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '应付合计（元）',
  `actual_expense_total` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '实付合计（元）',
  `accrual_balance` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '应收结余（元）',
  `actual_balance` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '实收结余（元）',
  `pending_approval_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '待审批实例ID',
  `draft_data` mediumtext COMMENT '当前未提交修改草稿，不作为历史版本',
  `create_by` int(11) unsigned NOT NULL DEFAULT '0',
  `update_by` int(11) unsigned NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  `submit_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_village_period` (`village_id`,`year`,`quarter`),
  KEY `idx_village_status` (`village_id`,`status`,`year`,`quarter`),
  KEY `idx_pending_approval` (`village_id`,`pending_approval_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公共收益季度数据';

CREATE TABLE IF NOT EXISTS `pigcms_public_revenue_detail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `village_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '小区ID',
  `period_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '季度数据ID',
  `subject_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '收支项目ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1收入 2支出',
  `accrual_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '收入为应收、支出为应付（元）',
  `actual_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '收入为实收、支出为实付（元）',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `sort` int(11) NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_village_period_subject` (`village_id`,`period_id`,`subject_id`),
  KEY `idx_village_type` (`village_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公共收益季度收支明细';

CREATE TABLE IF NOT EXISTS `pigcms_public_revenue_operation_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `village_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '小区ID',
  `business_type` varchar(50) NOT NULL DEFAULT 'public_revenue',
  `business_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `action` varchar(50) NOT NULL DEFAULT '',
  `operator_id` int(11) unsigned NOT NULL DEFAULT '0',
  `operator_wid` int(11) unsigned NOT NULL DEFAULT '0',
  `operator_name` varchar(80) NOT NULL DEFAULT '',
  `reason` varchar(500) NOT NULL DEFAULT '',
  `before_data` mediumtext,
  `after_data` mediumtext,
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_village_business` (`village_id`,`business_type`,`business_id`),
  KEY `idx_village_time` (`village_id`,`add_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公共收益操作日志';

-- 小区后台菜单，组件名与管理端动态组件注册表保持一致。
UPDATE `pigcms_house_menu_new`
SET `fid` = 1,
    `name` = '公共收益配置',
    `sort` = 99850,
    `show` = 1,
    `status` = 1,
    `type` = 1,
    `jump_type` = 3,
    `component` = 'publicRevenue',
    `path` = '/village/village.publicRevenue/publicRevenue',
    `is_hide` = 0
WHERE `component` = 'publicRevenue';

INSERT INTO `pigcms_house_menu_new`
  (`fid`,`name`,`module`,`action`,`select_module`,`select_action`,`choose_page`,`icon`,`sort`,`show`,`status`,`type`,`jump_type`,`jump_url`,`application_id`,`application_txt`,`component`,`path`,`is_hide`)
SELECT 1,'公共收益配置','','',' ',' ',' ','',99850,1,1,1,3,' ',0,' ','publicRevenue','/village/village.publicRevenue/publicRevenue',0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `pigcms_house_menu_new` WHERE `component` = 'publicRevenue'
);

-- 为每个已使用社区审批的小区增加系统内置类型，审批节点由小区自行配置。
INSERT IGNORE INTO `pigcms_community_approval_business_type`
  (`property_id`,`village_id`,`type_key`,`type_name`,`status`,`is_system`,`is_del`,`add_time`,`update_time`)
SELECT `property_id`,`village_id`,'public_revenue_change','修改公共收益',1,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM `pigcms_house_village`
WHERE `village_id` > 0
GROUP BY `property_id`,`village_id`;
