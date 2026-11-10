-- 公共收益金额单位统一为元，仅更新字段注释，不变更已有金额数值。
ALTER TABLE `pigcms_public_revenue_period`
  MODIFY COLUMN `opening_accrual_balance` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '应收口径期初结余（元）',
  MODIFY COLUMN `opening_actual_balance` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '实收口径期初结余（元）',
  MODIFY COLUMN `accrual_income_total` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '应收合计（元）',
  MODIFY COLUMN `actual_income_total` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '实收合计（元）',
  MODIFY COLUMN `accrual_expense_total` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '应付合计（元）',
  MODIFY COLUMN `actual_expense_total` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '实付合计（元）',
  MODIFY COLUMN `accrual_balance` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '应收结余（元）',
  MODIFY COLUMN `actual_balance` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '实收结余（元）';

ALTER TABLE `pigcms_public_revenue_detail`
  MODIFY COLUMN `accrual_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '收入为应收、支出为应付（元）',
  MODIFY COLUMN `actual_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT '收入为实收、支出为实付（元）';
