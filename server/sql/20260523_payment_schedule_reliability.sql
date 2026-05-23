SET FOREIGN_KEY_CHECKS=0;

-- P0 预约/支付/档期核心可靠性：支付交易号唯一约束兜底。
-- 说明：MySQL 唯一索引允许多条 NULL，适合待支付记录；已支付回调必须写入唯一 transaction_id。
-- 执行前如存在重复非空 transaction_id，请先人工核对异常支付记录后再创建唯一索引。
ALTER TABLE `la_payment`
    MODIFY COLUMN `transaction_id` VARCHAR(64) NULL DEFAULT NULL COMMENT '第三方交易号';

UPDATE `la_payment` SET `transaction_id` = NULL WHERE `transaction_id` = '';

ALTER TABLE `la_payment`
    DROP INDEX `idx_transaction_id`,
    ADD UNIQUE KEY `uk_transaction_id` (`transaction_id`);

SET FOREIGN_KEY_CHECKS=1;
