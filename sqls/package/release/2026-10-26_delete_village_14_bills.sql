-- 删除小区 14 的全部新收银台账单（应收、已缴、作废、退款，不按状态过滤）。
-- 本文件只生成，未执行。执行前备份数据库并暂停该小区收费、支付回调及自动出账任务。
-- 在同一个数据库连接内完整运行；任何语句报错立即 ROLLBACK，不要继续 COMMIT。
-- 此操作是物理删除，不会向付款人退款，也不会回退余额、积分、押金账户余额或服务期限。
-- 保留住户、收费项目/标准、押金分类、审批记录及业务账户资料。
-- 适用数据库：xmo2o，MySQL 5.7，表前缀 pigcms_。
USE `xmo2o`;
SET @target_village_id := 14;

SELECT village_id, is_paid, is_discard, is_refund, COUNT(*) AS bill_count
FROM pigcms_house_new_pay_order
WHERE village_id = @target_village_id
GROUP BY village_id, is_paid, is_discard, is_refund;

DROP TEMPORARY TABLE IF EXISTS tmp_v14_bill_ids;
DROP TEMPORARY TABLE IF EXISTS tmp_v14_summary_ids;
CREATE TEMPORARY TABLE tmp_v14_bill_ids (order_id BIGINT PRIMARY KEY) ENGINE=InnoDB;
CREATE TEMPORARY TABLE tmp_v14_summary_ids (summary_id BIGINT PRIMARY KEY) ENGINE=InnoDB;

START TRANSACTION;
INSERT INTO tmp_v14_bill_ids
SELECT order_id FROM pigcms_house_new_pay_order WHERE village_id = @target_village_id;
-- 只删除属于该小区、且没有其他小区账单引用的支付汇总。
INSERT INTO tmp_v14_summary_ids
SELECT s.summary_id FROM pigcms_house_new_pay_order_summary s
WHERE s.village_id = @target_village_id
AND NOT EXISTS (
    SELECT 1 FROM pigcms_house_new_pay_order o
    WHERE o.summary_id = s.summary_id AND o.village_id <> @target_village_id
);

SELECT COUNT(*) AS bills_to_delete FROM tmp_v14_bill_ids;
SELECT COUNT(*) AS summaries_to_delete FROM tmp_v14_summary_ids;

DELETE d FROM pigcms_house_new_pay_order_refund d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id;

DELETE d FROM pigcms_house_new_pay_order_modify_record d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id;

DELETE d FROM pigcms_house_new_order_log d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id;

DELETE d FROM pigcms_house_new_bill_deposit_freeze d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id;

DELETE d FROM pigcms_house_new_bill_deposit_order d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id;

DELETE d FROM pigcms_house_new_bill_third_party_split d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id;

DELETE d FROM pigcms_house_new_vacancy_fee_day d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id;

DELETE d FROM pigcms_house_new_select_project_record d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id;

-- 新版收银台开票关联记录：order_type=2，避免误删旧版订单同号记录。
DELETE d FROM pigcms_house_village_detail_record d
INNER JOIN tmp_v14_bill_ids t ON t.order_id = d.order_id
WHERE d.village_id = @target_village_id AND d.order_type = 2;

DELETE d FROM pigcms_house_village_order_ticket_number d
INNER JOIN tmp_v14_summary_ids t ON t.summary_id = d.summary_id
WHERE d.village_id = @target_village_id;

DELETE o FROM pigcms_house_new_pay_order o
INNER JOIN tmp_v14_bill_ids t ON t.order_id = o.order_id
WHERE o.village_id = @target_village_id;

DELETE s FROM pigcms_house_new_pay_order_summary s
INNER JOIN tmp_v14_summary_ids t ON t.summary_id = s.summary_id
WHERE s.village_id = @target_village_id;

-- 应为 0；若非 0，应 ROLLBACK 并排查是否有并发出账。
SELECT COUNT(*) AS remaining_bills
FROM pigcms_house_new_pay_order WHERE village_id = @target_village_id;

COMMIT;
DROP TEMPORARY TABLE tmp_v14_bill_ids;
DROP TEMPORARY TABLE tmp_v14_summary_ids;
