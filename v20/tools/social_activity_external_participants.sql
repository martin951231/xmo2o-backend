-- 从上一版参加人表升级，一次性执行；如需重复运行，请使用 PHP 迁移脚本。
ALTER TABLE `pigcms_social_activity_participant`
    ADD COLUMN `phone` VARCHAR(20) NOT NULL DEFAULT '',
    DROP INDEX `uq_order_member`,
    ADD KEY `ix_order_member` (`order_id`, `bind_id`),
    ADD UNIQUE KEY `uq_order_identity` (`order_id`, `id_number`),
    ADD KEY `ix_member_phone` (`phone`, `uid`);
