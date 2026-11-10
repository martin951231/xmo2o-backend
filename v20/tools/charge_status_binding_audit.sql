-- 只读核对：晋美华府周期收费绑定。若有同名小区，请改成明确的 h.village_id 条件。
-- 1. 按项目、标准、绑定类型、房屋类型和状态拆分，核对 56/54 与 680/700 的来源。
SELECT h.village_id, h.village_name,
       b.project_id, p.name AS project_name, p.status AS project_status, p.type AS project_type,
       n.charge_type, b.rule_id, r.charge_name, r.status AS rule_status,
       b.bind_type, v.house_type,
       COUNT(*) AS binding_rows,
       COUNT(DISTINCT CASE WHEN b.bind_type=1 THEN b.vacancy_id END) AS distinct_rooms,
       COUNT(DISTINCT CASE WHEN b.bind_type=2 THEN b.position_id END) AS distinct_positions,
       SUM(CASE WHEN b.bind_type=1 AND v.pigcms_id IS NULL THEN 1 ELSE 0 END) AS missing_or_deleted_rooms,
       SUM(CASE WHEN b.bind_type=2 AND pp.position_id IS NULL THEN 1 ELSE 0 END) AS missing_positions
FROM pigcms_house_new_charge_standard_bind b
JOIN pigcms_house_village h ON h.village_id=b.village_id
LEFT JOIN pigcms_house_new_charge_project p ON p.id=b.project_id
LEFT JOIN pigcms_house_new_charge_number n ON n.id=p.subject_id
LEFT JOIN pigcms_house_new_charge_rule r ON r.id=b.rule_id
LEFT JOIN pigcms_house_village_user_vacancy v
       ON v.pigcms_id=b.vacancy_id AND v.village_id=b.village_id AND v.is_del=0
LEFT JOIN pigcms_house_village_parking_position pp ON pp.position_id=b.position_id
WHERE h.village_name='晋美华府' AND b.is_del=1
  AND n.charge_type IN ('property','merchant_property','parking_management')
GROUP BY h.village_id,h.village_name,b.project_id,p.name,p.status,p.type,
         n.charge_type,b.rule_id,r.charge_name,r.status,b.bind_type,v.house_type
ORDER BY b.project_id,b.rule_id,b.bind_type,v.house_type;

-- 2. 逐条列出当前绑定及统计是否纳入，查找商铺差异的房屋和车位差异对象。
-- “纳入”指本条绑定直接合格；不合格记录的对象若另有合格绑定，仍可能被接口纳入。
SELECT h.village_id,b.id AS bind_id,b.project_id,p.name AS project_name,
       b.rule_id,r.charge_name,b.bind_type,b.vacancy_id,b.position_id,
       v.room,v.house_type,pp.position_num,
       CASE
         WHEN p.village_id<>b.village_id OR r.village_id<>b.village_id THEN '项目或标准小区不一致'
         WHEN r.id IS NULL OR r.charge_project_id<>b.project_id THEN '标准不存在或不属于该项目'
         WHEN p.status<>1 OR p.type<>2 THEN '项目非启用周期项目'
         WHEN r.status<>1 THEN '标准非启用状态，不能单独贡献总户数'
         WHEN b.bind_type=2 AND b.position_id<=0 THEN '车位ID无效'
         WHEN b.bind_type<>2 AND v.pigcms_id IS NULL THEN '房屋不存在、已删除或不属于当前小区'
         WHEN n.charge_type IN ('property','merchant_property') AND b.bind_type=2 THEN '物业费绑定车位，不纳入房屋户数'
         WHEN n.charge_type IN ('property','merchant_property') AND (v.house_type IS NULL OR v.house_type NOT IN (1,2)) THEN '房屋类型非住宅或商铺'
         WHEN n.charge_type IN ('property','merchant_property') AND v.house_type=1 THEN '纳入住宅，非商铺'
         WHEN n.charge_type IN ('property','merchant_property') AND v.house_type=2 THEN '纳入商铺'
         WHEN n.charge_type='parking_management' AND b.bind_type=2 THEN '纳入车位，按车位ID去重'
         WHEN n.charge_type='parking_management' THEN '纳入车位管理费，但绑定对象为房屋'
         ELSE '待核对'
       END AS scope_explanation
FROM pigcms_house_new_charge_standard_bind b
JOIN pigcms_house_village h ON h.village_id=b.village_id
LEFT JOIN pigcms_house_new_charge_project p ON p.id=b.project_id
LEFT JOIN pigcms_house_new_charge_number n ON n.id=p.subject_id
LEFT JOIN pigcms_house_new_charge_rule r ON r.id=b.rule_id
LEFT JOIN pigcms_house_village_user_vacancy v
       ON v.pigcms_id=b.vacancy_id AND v.village_id=b.village_id AND v.is_del=0
LEFT JOIN pigcms_house_village_parking_position pp ON pp.position_id=b.position_id
WHERE h.village_name='晋美华府' AND b.is_del=1
  AND n.charge_type IN ('property','merchant_property','parking_management')
ORDER BY b.project_id,b.bind_type,b.vacancy_id,b.position_id,b.rule_id;

-- 3. 同项目跨标准重复绑定；有多个历史标准不一定是错误，但这些对象户数只能计一次。
SELECT b.village_id,b.project_id,b.bind_type,
       CASE WHEN b.bind_type=2 THEN b.position_id ELSE b.vacancy_id END AS target_id,
       COUNT(*) AS binding_rows,
       GROUP_CONCAT(b.id ORDER BY b.id) AS bind_ids,
       GROUP_CONCAT(b.rule_id ORDER BY b.rule_id) AS rule_ids
FROM pigcms_house_new_charge_standard_bind b
JOIN pigcms_house_village h ON h.village_id=b.village_id
JOIN pigcms_house_new_charge_project p ON p.id=b.project_id
JOIN pigcms_house_new_charge_number n ON n.id=p.subject_id
WHERE h.village_name='晋美华府' AND b.is_del=1
  AND n.charge_type IN ('property','merchant_property','parking_management')
GROUP BY b.village_id,b.project_id,b.bind_type,
         CASE WHEN b.bind_type=2 THEN b.position_id ELSE b.vacancy_id END
HAVING COUNT(*)>1
ORDER BY b.project_id,b.bind_type,target_id;