-- =====================================================
-- 人员证书表结构升级
-- 创建时间：2026-04-13
-- 说明：
-- 1. 补齐证书类型、证书编号、驳回原因、新状态字段
-- 2. 兼容旧字段 certificate_no / audit_status
-- 3. 为后台筛选与搜索补齐索引
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @has_staff_certificate_type = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff_certificate'
      AND COLUMN_NAME = 'type'
);
SET @add_staff_certificate_type_sql = IF(
    @has_staff_certificate_type > 0,
    'SELECT 1',
    'ALTER TABLE `la_staff_certificate` ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''证书类型'' AFTER `name`'
);
PREPARE stmt FROM @add_staff_certificate_type_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_staff_certificate_sn = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff_certificate'
      AND COLUMN_NAME = 'sn'
);
SET @add_staff_certificate_sn_sql = IF(
    @has_staff_certificate_sn > 0,
    'SELECT 1',
    'ALTER TABLE `la_staff_certificate` ADD COLUMN `sn` VARCHAR(100) NOT NULL DEFAULT '''' COMMENT ''证书编号'' AFTER `type`'
);
PREPARE stmt FROM @add_staff_certificate_sn_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_staff_certificate_reject_reason = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff_certificate'
      AND COLUMN_NAME = 'reject_reason'
);
SET @add_staff_certificate_reject_reason_sql = IF(
    @has_staff_certificate_reject_reason > 0,
    'SELECT 1',
    'ALTER TABLE `la_staff_certificate` ADD COLUMN `reject_reason` VARCHAR(255) NOT NULL DEFAULT '''' COMMENT ''拒绝原因'' AFTER `expire_date`'
);
PREPARE stmt FROM @add_staff_certificate_reject_reason_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_staff_certificate_audit_status = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff_certificate'
      AND COLUMN_NAME = 'audit_status'
);
SET @has_staff_certificate_verify_status = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff_certificate'
      AND COLUMN_NAME = 'verify_status'
);
SET @add_staff_certificate_verify_status_sql = IF(
    @has_staff_certificate_verify_status > 0,
    'SELECT 1',
    IF(
        @has_staff_certificate_audit_status > 0,
        'ALTER TABLE `la_staff_certificate` ADD COLUMN `verify_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''审核状态:0-待审核,1-已通过,2-已拒绝'' AFTER `audit_status`',
        'ALTER TABLE `la_staff_certificate` ADD COLUMN `verify_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''审核状态:0-待审核,1-已通过,2-已拒绝'' AFTER `expire_date`'
    )
);
PREPARE stmt FROM @add_staff_certificate_verify_status_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_staff_certificate_no = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_staff_certificate'
      AND COLUMN_NAME = 'certificate_no'
);
SET @backfill_staff_certificate_sn_sql = IF(
    @has_staff_certificate_no > 0,
    'UPDATE `la_staff_certificate` SET `sn` = `certificate_no` WHERE `sn` = '''' AND `certificate_no` <> ''''',
    'SELECT 1'
);
PREPARE stmt FROM @backfill_staff_certificate_sn_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @backfill_staff_certificate_no_sql = IF(
    @has_staff_certificate_no > 0,
    'UPDATE `la_staff_certificate` SET `certificate_no` = `sn` WHERE `certificate_no` = '''' AND `sn` <> ''''',
    'SELECT 1'
);
PREPARE stmt FROM @backfill_staff_certificate_no_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @backfill_staff_certificate_verify_status_sql = IF(
    @has_staff_certificate_verify_status = 0 AND @has_staff_certificate_audit_status > 0,
    'UPDATE `la_staff_certificate` SET `verify_status` = `audit_status`',
    'SELECT 1'
);
PREPARE stmt FROM @backfill_staff_certificate_verify_status_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_staff_certificate_verify_status_index_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff_certificate'
          AND INDEX_NAME = 'idx_verify_status'
    ),
    'SELECT 1',
    'ALTER TABLE `la_staff_certificate` ADD KEY `idx_verify_status` (`verify_status`)'
);
PREPARE stmt FROM @add_staff_certificate_verify_status_index_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_staff_certificate_sn_index_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff_certificate'
          AND INDEX_NAME = 'idx_sn'
    ),
    'SELECT 1',
    'ALTER TABLE `la_staff_certificate` ADD KEY `idx_sn` (`sn`)'
);
PREPARE stmt FROM @add_staff_certificate_sn_index_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
