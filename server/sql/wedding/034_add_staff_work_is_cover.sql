-- =============================================
-- 员工作品表补充封面字段
-- 说明：为 la_staff_work 增加 is_cover 字段，用于后台作品筛选与设为封面能力
-- =============================================

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff_work'
      AND COLUMN_NAME = 'is_cover'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `la_staff_work` ADD COLUMN `is_cover` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''是否封面:0-否,1-是'' AFTER `is_show`',
    'SELECT "Column is_cover already exists, skipping..."'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
