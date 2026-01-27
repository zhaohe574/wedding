-- 添加动态评论开关字段（简化版）
-- 日期: 2025-01-24
-- 描述: 为 la_dynamic 表添加 allow_comment 字段

-- 检查字段是否已存在，如果不存在则添加
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'la_dynamic' 
    AND COLUMN_NAME = 'allow_comment');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `la_dynamic` ADD COLUMN `allow_comment` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''是否允许评论：0=禁止，1=允许'' AFTER `tags`', 
    'SELECT "Column allow_comment already exists, skipping..."');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 确保所有现有记录的 allow_comment 字段为 1
UPDATE `la_dynamic` SET `allow_comment` = 1 WHERE `allow_comment` = 0 OR `allow_comment` IS NULL;
