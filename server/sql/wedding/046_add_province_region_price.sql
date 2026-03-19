-- =====================================================
-- 省级地区定价扩展
-- 创建时间：2026-03-18
-- 说明：
-- 1. 套餐地区价格支持省级定价
-- 2. 调整地区价格唯一索引，支持省 / 市 / 区三级共存
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `la_service_package_region_price`
    MODIFY COLUMN `region_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '地区层级：1=省，2=市，3=区县';

SET @drop_old_uniq_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_package_region_price'
          AND INDEX_NAME = 'uniq_package_region'
    ),
    'ALTER TABLE `la_service_package_region_price` DROP INDEX `uniq_package_region`',
    'SELECT 1'
);
PREPARE stmt FROM @drop_old_uniq_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_new_uniq_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_package_region_price'
          AND INDEX_NAME = 'uniq_package_region'
    ),
    'SELECT 1',
    'ALTER TABLE `la_service_package_region_price` ADD UNIQUE KEY `uniq_package_region` (`package_id`, `region_level`, `province_code`, `city_code`, `district_code`)'
);
PREPARE stmt FROM @add_new_uniq_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_region_match_idx_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_package_region_price'
          AND INDEX_NAME = 'idx_province_city_district'
    ),
    'SELECT 1',
    'ALTER TABLE `la_service_package_region_price` ADD KEY `idx_province_city_district` (`province_code`, `city_code`, `district_code`)'
);
PREPARE stmt FROM @add_region_match_idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
