-- 将押金解冻、账单退款、账单作废统一为系统内置审批业务类型，并同步到全部小区。
-- 说明：本脚本只同步业务类型并迁移已有同名审批流，不会凭空创建审批流节点；
--       没有配置审批流的小区仍需在后台为对应业务类型配置审批人。

START TRANSACTION;

-- 已存在标准 key 的记录直接恢复、启用并标记为系统内置。
UPDATE `pigcms_community_approval_business_type`
SET `type_name` = CASE `type_key`
        WHEN 'deposit_release' THEN '押金解冻'
        WHEN 'order_refund' THEN '账单退款'
        WHEN 'order_discard' THEN '账单作废'
    END,
    `status` = 1,
    `is_system` = 1,
    `is_del` = 0,
    `update_time` = UNIX_TIMESTAMP()
WHERE `type_key` IN ('deposit_release', 'order_refund', 'order_discard');

-- 为所有已归属物业公司的小区补齐三个系统内置类型。
INSERT INTO `pigcms_community_approval_business_type`
    (`property_id`, `village_id`, `type_key`, `type_name`, `status`, `is_system`, `is_del`, `add_time`, `update_time`)
SELECT v.`property_id`, v.`village_id`, t.`type_key`, t.`type_name`, 1, 1, 0,
       UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `pigcms_house_village` v
CROSS JOIN (
    SELECT 'deposit_release' AS `type_key`, '押金解冻' AS `type_name`
    UNION ALL SELECT 'order_refund', '账单退款'
    UNION ALL SELECT 'order_discard', '账单作废'
) t
WHERE v.`property_id` > 0
  AND NOT EXISTS (
      SELECT 1
      FROM `pigcms_community_approval_business_type` bt
      WHERE bt.`property_id` = v.`property_id`
        AND bt.`village_id` = v.`village_id`
        AND bt.`type_key` = t.`type_key`
        AND bt.`is_del` = 0
  );

-- 每个小区统一保留一套押金解冻流程：优先已有 deposit_release 启用流程，
-- 其次取最新启用的押金分类流程；没有启用流程时再取最新一条已有流程。
DROP TEMPORARY TABLE IF EXISTS `_tmp_deposit_flow_choice`;
CREATE TEMPORARY TABLE `_tmp_deposit_flow_choice` AS
SELECT system_bt.`property_id`, system_bt.`village_id`, system_bt.`id` AS `system_type_id`,
       COALESCE(
           MAX(CASE WHEN f.`status` = 1 AND (old_bt.`id` = system_bt.`id` OR f.`business_type` = 'deposit_release') THEN f.`id` END),
           MAX(CASE WHEN f.`status` = 1 AND (c.`id` IS NOT NULL OR old_bt.`type_name` = '押金解冻') THEN f.`id` END),
           MAX(CASE WHEN old_bt.`id` = system_bt.`id` OR f.`business_type` = 'deposit_release' THEN f.`id` END),
           MAX(CASE WHEN c.`id` IS NOT NULL OR old_bt.`type_name` = '押金解冻' THEN f.`id` END)
       ) AS `chosen_flow_id`
FROM `pigcms_community_approval_business_type` system_bt
LEFT JOIN `pigcms_community_approval_flow` f
  ON f.`property_id` = system_bt.`property_id`
 AND f.`village_id` = system_bt.`village_id`
 AND f.`is_del` = 0
LEFT JOIN `pigcms_community_approval_business_type` old_bt
  ON old_bt.`property_id` = f.`property_id`
 AND old_bt.`village_id` = f.`village_id`
 AND (old_bt.`id` = f.`business_type_id` OR old_bt.`type_key` = f.`business_type`)
LEFT JOIN `pigcms_house_new_deposit_category` c
  ON c.`village_id` = f.`village_id`
 AND c.`cate_key` = old_bt.`type_key`
WHERE system_bt.`type_key` = 'deposit_release'
  AND system_bt.`is_del` = 0
GROUP BY system_bt.`property_id`, system_bt.`village_id`, system_bt.`id`;

-- 被选中的押金审批流改绑到系统内置 deposit_release。
UPDATE `pigcms_community_approval_flow` f
JOIN `_tmp_deposit_flow_choice` x ON x.`chosen_flow_id` = f.`id`
SET f.`business_type_id` = x.`system_type_id`,
    f.`business_type` = 'deposit_release',
    f.`update_time` = UNIX_TIMESTAMP();

-- 同一小区其余押金分类/押金解冻流程停用，避免一个系统类型随机命中多套流程。
UPDATE `pigcms_community_approval_flow` f
JOIN `_tmp_deposit_flow_choice` x
  ON x.`property_id` = f.`property_id`
 AND x.`village_id` = f.`village_id`
JOIN `pigcms_community_approval_business_type` old_bt
  ON old_bt.`property_id` = f.`property_id`
 AND old_bt.`village_id` = f.`village_id`
 AND (old_bt.`id` = f.`business_type_id` OR old_bt.`type_key` = f.`business_type`)
LEFT JOIN `pigcms_house_new_deposit_category` c
  ON c.`village_id` = f.`village_id`
 AND c.`cate_key` = old_bt.`type_key`
SET f.`status` = 0,
    f.`update_time` = UNIX_TIMESTAMP()
WHERE f.`is_del` = 0
  AND x.`chosen_flow_id` IS NOT NULL
  AND f.`id` <> x.`chosen_flow_id`
  AND (old_bt.`id` = x.`system_type_id` OR old_bt.`type_name` = '押金解冻' OR c.`id` IS NOT NULL);

-- 把历史上手工添加的账单退款、账单作废同名类型所绑定的审批流迁移到标准系统类型。
UPDATE `pigcms_community_approval_flow` f
JOIN `pigcms_community_approval_business_type` old_bt
  ON old_bt.`property_id` = f.`property_id`
 AND old_bt.`village_id` = f.`village_id`
 AND (old_bt.`id` = f.`business_type_id` OR old_bt.`type_key` = f.`business_type`)
JOIN `pigcms_community_approval_business_type` system_bt
  ON system_bt.`property_id` = f.`property_id`
 AND system_bt.`village_id` = f.`village_id`
 AND system_bt.`type_key` = CASE old_bt.`type_name`
        WHEN '账单退款' THEN 'order_refund'
        WHEN '账单作废' THEN 'order_discard'
     END
 AND system_bt.`is_del` = 0
SET f.`business_type_id` = system_bt.`id`,
    f.`business_type` = system_bt.`type_key`,
    f.`update_time` = UNIX_TIMESTAMP()
WHERE old_bt.`type_name` IN ('账单退款', '账单作废')
  AND old_bt.`type_key` <> system_bt.`type_key`;

-- 兼容历史审批流只保存 business_type、没有保存 business_type_id 的情况。
UPDATE `pigcms_community_approval_flow` f
JOIN `pigcms_community_approval_business_type` system_bt
  ON system_bt.`property_id` = f.`property_id`
 AND system_bt.`village_id` = f.`village_id`
 AND system_bt.`type_key` = f.`business_type`
 AND system_bt.`is_del` = 0
SET f.`business_type_id` = system_bt.`id`,
    f.`update_time` = UNIX_TIMESTAMP()
WHERE f.`business_type` IN ('deposit_release', 'order_refund', 'order_discard')
  AND (f.`business_type_id` IS NULL OR f.`business_type_id` = 0 OR f.`business_type_id` <> system_bt.`id`);

-- 历史审批实例同步业务类型，保证列表、详情和业务生效判断一致。
UPDATE `pigcms_community_approval_instance` i
JOIN `pigcms_community_approval_business_type` old_bt
  ON old_bt.`property_id` = i.`property_id`
 AND old_bt.`village_id` = i.`village_id`
 AND (old_bt.`id` = i.`business_type_id` OR old_bt.`type_key` = i.`business_type`)
JOIN `pigcms_community_approval_business_type` system_bt
  ON system_bt.`property_id` = i.`property_id`
 AND system_bt.`village_id` = i.`village_id`
 AND system_bt.`type_key` = CASE old_bt.`type_name`
        WHEN '押金解冻' THEN 'deposit_release'
        WHEN '账单退款' THEN 'order_refund'
        WHEN '账单作废' THEN 'order_discard'
     END
 AND system_bt.`is_del` = 0
SET i.`business_type_id` = system_bt.`id`,
    i.`business_type` = system_bt.`type_key`,
    i.`update_time` = UNIX_TIMESTAMP()
WHERE old_bt.`type_name` IN ('押金解冻', '账单退款', '账单作废')
  AND old_bt.`type_key` <> system_bt.`type_key`;

-- 历史押金分类审批实例全部改绑到 deposit_release。
UPDATE `pigcms_community_approval_instance` i
JOIN `pigcms_community_approval_business_type` old_bt
  ON old_bt.`property_id` = i.`property_id`
 AND old_bt.`village_id` = i.`village_id`
 AND (old_bt.`id` = i.`business_type_id` OR old_bt.`type_key` = i.`business_type`)
JOIN `pigcms_house_new_deposit_category` c
  ON c.`property_id` = i.`property_id`
 AND c.`village_id` = i.`village_id`
 AND c.`cate_key` = old_bt.`type_key`
JOIN `pigcms_community_approval_business_type` system_bt
  ON system_bt.`property_id` = i.`property_id`
 AND system_bt.`village_id` = i.`village_id`
 AND system_bt.`type_key` = 'deposit_release'
 AND system_bt.`is_del` = 0
SET i.`business_type_id` = system_bt.`id`,
    i.`business_type` = 'deposit_release',
    i.`update_time` = UNIX_TIMESTAMP()
WHERE old_bt.`type_key` <> 'deposit_release';

-- 押金解冻历史实例可能使用押金分类 key；按 change_data 中的业务动作统一迁移。
UPDATE `pigcms_community_approval_instance` i
JOIN `pigcms_community_approval_business_type` system_bt
  ON system_bt.`property_id` = i.`property_id`
 AND system_bt.`village_id` = i.`village_id`
 AND system_bt.`type_key` = 'deposit_release'
 AND system_bt.`is_del` = 0
SET i.`business_type_id` = system_bt.`id`,
    i.`business_type` = system_bt.`type_key`,
    i.`update_time` = UNIX_TIMESTAMP()
WHERE i.`change_data` LIKE '%"action":"deposit_release"%'
  AND (i.`business_type_id` IS NULL OR i.`business_type_id` = 0
       OR i.`business_type_id` <> system_bt.`id` OR i.`business_type` <> system_bt.`type_key`);

-- 软删除已完成迁移的手工同名类型，避免业务类型列表重复展示。
UPDATE `pigcms_community_approval_business_type`
SET `is_del` = 1,
    `status` = 0,
    `update_time` = UNIX_TIMESTAMP()
WHERE `type_name` IN ('押金解冻', '账单退款', '账单作废')
  AND `type_key` NOT IN ('deposit_release', 'order_refund', 'order_discard')
  AND `is_system` = 0;

-- 押金分类 key 只保留在押金分类表作为业务元数据，不再作为审批业务类型。
UPDATE `pigcms_community_approval_business_type` bt
JOIN `pigcms_house_new_deposit_category` c
  ON c.`property_id` = bt.`property_id`
 AND c.`village_id` = bt.`village_id`
 AND c.`cate_key` = bt.`type_key`
SET bt.`is_del` = 1,
    bt.`status` = 0,
    bt.`update_time` = UNIX_TIMESTAMP()
WHERE bt.`type_key` <> 'deposit_release'
  AND bt.`is_system` = 0;

DROP TEMPORARY TABLE IF EXISTS `_tmp_deposit_flow_choice`;

COMMIT;

-- 执行后核查：每个小区应各有 3 条系统内置记录。
SELECT v.`property_id`, v.`village_id`, COUNT(bt.`id`) AS `system_type_count`
FROM `pigcms_house_village` v
LEFT JOIN `pigcms_community_approval_business_type` bt
  ON bt.`property_id` = v.`property_id`
 AND bt.`village_id` = v.`village_id`
 AND bt.`type_key` IN ('deposit_release', 'order_refund', 'order_discard')
 AND bt.`is_system` = 1
 AND bt.`is_del` = 0
WHERE v.`property_id` > 0
GROUP BY v.`property_id`, v.`village_id`
HAVING COUNT(bt.`id`) <> 3;

-- 核查哪些小区尚未给三类业务配置启用的审批流（这些小区创建业务时会提示先配置审批流）。
SELECT bt.`property_id`, bt.`village_id`, bt.`type_key`, bt.`type_name`
FROM `pigcms_community_approval_business_type` bt
LEFT JOIN `pigcms_community_approval_flow` f
  ON f.`property_id` = bt.`property_id`
 AND f.`village_id` = bt.`village_id`
 AND f.`is_del` = 0
 AND f.`status` = 1
 AND (f.`business_type_id` = bt.`id` OR f.`business_type` = bt.`type_key`)
WHERE bt.`type_key` IN ('deposit_release', 'order_refund', 'order_discard')
  AND bt.`is_system` = 1
  AND bt.`is_del` = 0
  AND f.`id` IS NULL
ORDER BY bt.`village_id`, bt.`type_key`;
