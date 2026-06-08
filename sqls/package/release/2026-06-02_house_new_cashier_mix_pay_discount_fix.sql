ALTER TABLE `pigcms_house_new_offline_pay`
  MODIFY COLUMN `money_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1=cash real payment, 2=non-cash deduction';

UPDATE `pigcms_house_new_offline_pay`
SET `money_type` = 2;

UPDATE `pigcms_house_new_offline_pay`
SET `money_type` = 1
WHERE LOCATE(CONVERT(0xE78EB0E98791 USING utf8mb4), CONVERT(`name` USING utf8mb4)) > 0
   OR LOCATE('cash', LOWER(CONVERT(`name` USING utf8mb4))) > 0;
