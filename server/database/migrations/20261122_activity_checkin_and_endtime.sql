-- 活动结束时间 + 活动入场核销码与签到闭环（2026-11-22）
-- 幂等执行：支持重复执行而不报错

SET @db_name = DATABASE();

-- 1. la_dynamic 新增 activity_end_time
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_dynamic'
      AND COLUMN_NAME = 'activity_end_time'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `la_dynamic` ADD COLUMN `activity_end_time` int(10) unsigned NOT NULL DEFAULT ''0'' COMMENT ''活动结束时间'' AFTER `activity_start_time`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. la_activity_registration 新增核销码与签到字段
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_activity_registration'
      AND COLUMN_NAME = 'checkin_code'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `la_activity_registration` ADD COLUMN `checkin_code` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '''' COMMENT ''入场核销码(8位大写字母数字)'' AFTER `payment_sn`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_activity_registration'
      AND COLUMN_NAME = 'checkin_status'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `la_activity_registration` ADD COLUMN `checkin_status` tinyint(3) unsigned NOT NULL DEFAULT ''0'' COMMENT ''签到核销状态：0=未签到,1=已签到'' AFTER `checkin_code`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_activity_registration'
      AND COLUMN_NAME = 'checkin_time'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `la_activity_registration` ADD COLUMN `checkin_time` int(10) unsigned NOT NULL DEFAULT ''0'' COMMENT ''签到核销时间'' AFTER `checkin_status`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_activity_registration'
      AND COLUMN_NAME = 'checkin_operator_type'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `la_activity_registration` ADD COLUMN `checkin_operator_type` tinyint(3) unsigned NOT NULL DEFAULT ''0'' COMMENT ''核销操作人类型：0=无,1=管理员,2=服务人员'' AFTER `checkin_time`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_activity_registration'
      AND COLUMN_NAME = 'checkin_operator_id'
);
SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE `la_activity_registration` ADD COLUMN `checkin_operator_id` int(10) unsigned NOT NULL DEFAULT ''0'' COMMENT ''核销操作人ID'' AFTER `checkin_operator_type`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. 新增核销索引
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_activity_registration'
      AND INDEX_NAME = 'idx_checkin_code'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE `la_activity_registration` ADD KEY `idx_checkin_code` (`checkin_code`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'la_activity_registration'
      AND INDEX_NAME = 'idx_checkin_status'
);
SET @sql = IF(
    @idx_exists = 0,
    'ALTER TABLE `la_activity_registration` ADD KEY `idx_checkin_status` (`checkin_status`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. 回填存量已报名且无核销码记录（8 位大写字母数字）
UPDATE `la_activity_registration`
SET `checkin_code` = UPPER(SUBSTRING(REPLACE(SHA2(CONCAT('ACT_', `id`, '_', `user_id`, '_', `create_time`, '_', RAND()), 256), '0', 'X'), 1, 8))
WHERE `registration_status` = 1
  AND (`checkin_code` IS NULL OR `checkin_code` = '');

-- 5. la_system_menu 幂等补登记核销权限节点（挂在 growth.dynamic/activityRegistrations 同级 pid 下）
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `perms`, `create_time`, `update_time`)
SELECT MIN(`pid`), 'A', '活动核销预览', 'growth.dynamic/activityCheckinPreview', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/activityRegistrations'
HAVING MIN(`pid`) IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/activityCheckinPreview');

INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `perms`, `create_time`, `update_time`)
SELECT MIN(`pid`), 'A', '活动签到核销', 'growth.dynamic/activityCheckin', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/activityRegistrations'
HAVING MIN(`pid`) IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/activityCheckin');
