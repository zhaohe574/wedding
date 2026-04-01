-- =============================================
-- 新预约流程字段升级
-- 说明：
-- 1. 下线旧 addon 预约链路后，新增服务人员自定义附加项配置
-- 2. 新增分类级别的管家/督导关联配置
-- 3. 订单项支持主服务 / 自定义附加项 / 关联服务人员三类
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff'
          AND COLUMN_NAME = 'booking_option_1_name'
    ),
    'SELECT 1',
    'ALTER TABLE `la_staff` ADD COLUMN `booking_option_1_name` VARCHAR(100) NOT NULL DEFAULT '''' COMMENT ''预约附加项1名称'' AFTER `service_desc`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff'
          AND COLUMN_NAME = 'booking_option_1_price'
    ),
    'SELECT 1',
    'ALTER TABLE `la_staff` ADD COLUMN `booking_option_1_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT ''预约附加项1价格'' AFTER `booking_option_1_name`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff'
          AND COLUMN_NAME = 'booking_option_2_name'
    ),
    'SELECT 1',
    'ALTER TABLE `la_staff` ADD COLUMN `booking_option_2_name` VARCHAR(100) NOT NULL DEFAULT '''' COMMENT ''预约附加项2名称'' AFTER `booking_option_1_price`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff'
          AND COLUMN_NAME = 'booking_option_2_price'
    ),
    'SELECT 1',
    'ALTER TABLE `la_staff` ADD COLUMN `booking_option_2_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT ''预约附加项2价格'' AFTER `booking_option_2_name`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_category'
          AND COLUMN_NAME = 'booking_butler_enabled'
    ),
    'SELECT 1',
    'ALTER TABLE `la_service_category` ADD COLUMN `booking_butler_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''是否启用婚礼管家预约：0-否，1-是'' AFTER `image`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_category'
          AND COLUMN_NAME = 'booking_butler_category_id'
    ),
    'SELECT 1',
    'ALTER TABLE `la_service_category` ADD COLUMN `booking_butler_category_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''婚礼管家关联服务分类ID'' AFTER `booking_butler_enabled`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_category'
          AND COLUMN_NAME = 'booking_director_enabled'
    ),
    'SELECT 1',
    'ALTER TABLE `la_service_category` ADD COLUMN `booking_director_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''是否启用婚礼督导预约：0-否，1-是'' AFTER `booking_butler_category_id`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_category'
          AND COLUMN_NAME = 'booking_director_category_id'
    ),
    'SELECT 1',
    'ALTER TABLE `la_service_category` ADD COLUMN `booking_director_category_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''婚礼督导关联服务分类ID'' AFTER `booking_director_enabled`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order_item'
          AND COLUMN_NAME = 'item_type'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order_item` ADD COLUMN `item_type` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''订单项类型：1=主服务，2=预约附加项，3=关联服务人员'' AFTER `subtotal`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order_item'
          AND COLUMN_NAME = 'item_meta'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order_item` ADD COLUMN `item_meta` TEXT NULL COMMENT ''订单项扩展信息(JSON)'' AFTER `item_type`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
