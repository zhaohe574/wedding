-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 订单项套餐说明快照
-- +----------------------------------------------------------------------

SET @package_description_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_order_item'
      AND COLUMN_NAME = 'package_description'
);

SET @sql = IF(
    @package_description_exists > 0,
    'SELECT 1',
    'ALTER TABLE `la_order_item` ADD COLUMN `package_description` VARCHAR(500) NOT NULL DEFAULT '''' COMMENT ''套餐说明快照'' AFTER `package_name`'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
