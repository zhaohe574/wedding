-- =====================================================
-- 订单支付规则快照字段
-- 创建时间：2026-04-02
-- 说明：
-- 1. 为 la_order 增加定金规则快照字段
-- 2. 新订单创建时固化定金模式、类型、值与说明
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @add_deposit_mode_enabled_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'deposit_mode_enabled'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `deposit_mode_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''是否开启定金模式快照'' AFTER `balance_paid`'
);
PREPARE stmt FROM @add_deposit_mode_enabled_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_deposit_type_snapshot_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'deposit_type_snapshot'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `deposit_type_snapshot` VARCHAR(20) NOT NULL DEFAULT '''' COMMENT ''定金类型快照'' AFTER `deposit_mode_enabled`'
);
PREPARE stmt FROM @add_deposit_type_snapshot_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_deposit_value_snapshot_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'deposit_value_snapshot'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `deposit_value_snapshot` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT ''定金值快照'' AFTER `deposit_type_snapshot`'
);
PREPARE stmt FROM @add_deposit_value_snapshot_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_deposit_remark_snapshot_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'deposit_remark_snapshot'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `deposit_remark_snapshot` VARCHAR(255) NOT NULL DEFAULT '''' COMMENT ''定金说明快照'' AFTER `deposit_value_snapshot`'
);
PREPARE stmt FROM @add_deposit_remark_snapshot_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `la_order`
SET `deposit_mode_enabled` = CASE WHEN `deposit_amount` > 0 THEN 1 ELSE 0 END
WHERE `deposit_mode_enabled` = 0;

SET FOREIGN_KEY_CHECKS = 1;
