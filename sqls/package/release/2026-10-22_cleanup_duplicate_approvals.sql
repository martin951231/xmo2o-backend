-- 仅清理北京时间 2026-10-22 创建的重复审批。先部署并发防重修复，并暂停审批操作后执行。
-- 保留已处理记录；全部未处理时保留最早一条。业务内容必须完全一致。
SET time_zone = '+08:00';
SET @cleanup_start = UNIX_TIMESTAMP('2026-10-22 00:00:00');
SET @cleanup_end = UNIX_TIMESTAMP('2026-10-23 00:00:00');
DROP TEMPORARY TABLE IF EXISTS approval_duplicate_ids;
CREATE TEMPORARY TABLE approval_duplicate_ids (id BIGINT PRIMARY KEY);
START TRANSACTION;
INSERT INTO approval_duplicate_ids (id)
SELECT DISTINCT i.id
FROM pigcms_community_approval_instance i
JOIN pigcms_community_approval_instance k
  ON k.property_id=i.property_id AND k.village_id=i.village_id
 AND k.business_type=i.business_type AND k.business_id=i.business_id
 AND k.flow_id=i.flow_id AND k.apply_wid=i.apply_wid
 AND BINARY k.change_data=BINARY i.change_data
 AND k.add_time>=@cleanup_start AND k.add_time<@cleanup_end
 AND k.id<>i.id
WHERE i.add_time>=@cleanup_start AND i.add_time<@cleanup_end
 AND i.business_id>0
 AND i.business_type IN ('order_discard','order_refund','bill_amount_change','deposit_release')
 AND i.status=0
 AND NOT EXISTS (SELECT 1 FROM pigcms_community_approval_instance_node n WHERE n.instance_id=i.id AND (n.status<>0 OR n.approve_time>0))
 AND NOT EXISTS (SELECT 1 FROM pigcms_community_approval_record r WHERE r.instance_id=i.id AND r.action<>'submit')
 AND (k.status=1 OR (k.status=0 AND (
       k.id<i.id
       OR EXISTS (SELECT 1 FROM pigcms_community_approval_instance_node n WHERE n.instance_id=k.id AND (n.status<>0 OR n.approve_time>0))
       OR EXISTS (SELECT 1 FROM pigcms_community_approval_record r WHERE r.instance_id=k.id AND r.action<>'submit')
 )));
-- 查看本次删除对象。
SELECT i.id,i.business_type,i.business_id,i.status,FROM_UNIXTIME(i.add_time) created
FROM pigcms_community_approval_instance i JOIN approval_duplicate_ids d ON d.id=i.id ORDER BY i.id;
DELETE n FROM pigcms_community_approval_instance_node n JOIN approval_duplicate_ids d ON d.id=n.instance_id;
DELETE r FROM pigcms_community_approval_record r JOIN approval_duplicate_ids d ON d.id=r.instance_id;
DELETE i FROM pigcms_community_approval_instance i JOIN approval_duplicate_ids d ON d.id=i.id;
SELECT ROW_COUNT() deleted_approvals;
COMMIT;
DROP TEMPORARY TABLE approval_duplicate_ids;
