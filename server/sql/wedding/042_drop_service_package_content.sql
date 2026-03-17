-- =====================================================
-- 042. 删除套餐内容字段
-- 说明：套餐内容展示链路已整体下线，仅保留套餐描述字段。
-- 执行日期：2026-03-17
-- =====================================================

SET @drop_service_package_content_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_package'
          AND COLUMN_NAME = 'content'
    ),
    'ALTER TABLE `la_service_package` DROP COLUMN `content`',
    'SELECT 1'
);

PREPARE stmt FROM @drop_service_package_content_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
