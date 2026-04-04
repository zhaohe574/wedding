-- =============================================
-- 员工作品表补充作品类型字段
-- 说明：为 la_staff_work 增加 type 字段，用于后台作品筛选与作品类型展示
-- =============================================

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff_work'
      AND COLUMN_NAME = 'type'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `la_staff_work` ADD COLUMN `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''作品类型:1-图片,2-视频'' AFTER `title`',
    'SELECT "Column type already exists, skipping..."'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
