ALTER TABLE `pigcms_house_new_offline_pay`
  ADD COLUMN `money_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1=cash real payment, 2=non-cash deduction' AFTER `name`;

UPDATE `pigcms_house_new_offline_pay`
SET `money_type` = 1
WHERE LOCATE(CONVERT(0xE78EB0E98791 USING utf8mb4), CONVERT(`name` USING utf8mb4)) > 0
   OR LOCATE('cash', LOWER(CONVERT(`name` USING utf8mb4))) > 0;

ALTER TABLE `pigcms_house_new_pay_order`
  ADD COLUMN `discount_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'offline mix allocated discount money' AFTER `pay_money`,
  ADD COLUMN `discount_detail` text COMMENT 'offline mix allocated discount detail JSON' AFTER `discount_money`;

ALTER TABLE `pigcms_house_new_pay_order_summary`
  ADD COLUMN `discount_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'offline mix total discount money' AFTER `pay_money`,
  ADD COLUMN `pay_detail` text COMMENT 'offline mix payment detail JSON' AFTER `discount_money`;
