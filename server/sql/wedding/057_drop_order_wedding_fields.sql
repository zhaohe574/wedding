-- =====================================================
-- 删除订单表冗余婚礼字段
-- 创建时间：2026-04-02
-- 说明：
-- 1. 订单域统一只保留服务日期 / 服务地区 / 服务地址
-- 2. CRM 客户表仍保留 wedding_date / wedding_venue
-- 3. 不迁移历史订单数据，仅移除订单表字段
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @drop_order_wedding_date_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'wedding_date'
    ),
    'ALTER TABLE `la_order` DROP COLUMN `wedding_date`',
    'SELECT 1'
);
PREPARE stmt FROM @drop_order_wedding_date_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_order_wedding_venue_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'wedding_venue'
    ),
    'ALTER TABLE `la_order` DROP COLUMN `wedding_venue`',
    'SELECT 1'
);
PREPARE stmt FROM @drop_order_wedding_venue_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
