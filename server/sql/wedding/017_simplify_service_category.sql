-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 服务分类表结构简化
-- +----------------------------------------------------------------------
-- | 说明：移除冗余的 pid 和 level 字段，简化为扁平化结构
-- | 创建时间：2026-01-23
-- +----------------------------------------------------------------------

-- 检查并移除 level 字段（如果存在）
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'la_service_category' 
    AND COLUMN_NAME = 'level');

SET @sql = IF(@col_exists > 0, 
    'ALTER TABLE `la_service_category` DROP COLUMN `level`', 
    'SELECT "Column level does not exist, skipping..."');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 注意：不删除 pid 字段，因为它在原始表结构中存在
-- 如果需要删除 pid 字段，请确认业务逻辑不再需要它

-- 移除 level 相关索引（如果存在）
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'la_service_category' 
    AND INDEX_NAME = 'idx_level');

SET @sql = IF(@idx_exists > 0, 
    'ALTER TABLE `la_service_category` DROP INDEX `idx_level`', 
    'SELECT "Index idx_level does not exist, skipping..."');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
