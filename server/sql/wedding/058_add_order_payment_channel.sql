-- ============================================================
-- 订单增加付款渠道字段
-- 创建日期: 2026-04-03
-- ============================================================

SET @schema_name = DATABASE();
SET @payment_channel_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'la_order'
      AND COLUMN_NAME = 'payment_channel'
);

SET @payment_channel_sql = IF(
    @payment_channel_exists = 0,
    'ALTER TABLE `la_order` ADD COLUMN `payment_channel` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT ''付款渠道：1=线上支付,2=线下支付'' AFTER `pay_type`',
    'SELECT 1'
);

PREPARE payment_channel_stmt FROM @payment_channel_sql;
EXECUTE payment_channel_stmt;
DEALLOCATE PREPARE payment_channel_stmt;

UPDATE `la_order`
SET `payment_channel` = CASE
    WHEN `pay_type` = 4 OR COALESCE(`pay_voucher`, '') <> '' THEN 2
    ELSE 1
END
WHERE `payment_channel` NOT IN (1, 2) OR `payment_channel` IS NULL;
