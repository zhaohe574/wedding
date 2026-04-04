-- =============================================
-- 服务回访表补齐新版字段结构
-- 说明：兼容旧版 la_service_callback 表结构，补齐当前代码依赖字段与索引
-- =============================================

SET @table_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
);

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'callback_sn'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `callback_sn` VARCHAR(32) NOT NULL DEFAULT '''' COMMENT ''回访编号'' AFTER `id`',
        'SELECT "Column callback_sn already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'type'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''回访类型 1服务前 2服务中 3服务后'' AFTER `staff_id`',
        'SELECT "Column type already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'method'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `method` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''回访方式 1电话 2短信 3微信 4小程序问卷'' AFTER `type`',
        'SELECT "Column method already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'plan_time'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `plan_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''计划回访时间'' AFTER `status`',
        'SELECT "Column plan_time already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'actual_time'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `actual_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''实际回访时间'' AFTER `plan_time`',
        'SELECT "Column actual_time already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'admin_id'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''回访人ID'' AFTER `actual_time`',
        'SELECT "Column admin_id already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'duration'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `duration` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''回访时长（秒）'' AFTER `admin_id`',
        'SELECT "Column duration already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'score'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `score` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''满意度评分 0未评 1-5星'' AFTER `duration`',
        'SELECT "Column score already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'score_service'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `score_service` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''服务态度评分'' AFTER `score`',
        'SELECT "Column score_service already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'score_professional'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `score_professional` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''专业水平评分'' AFTER `score_service`',
        'SELECT "Column score_professional already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'score_punctual'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `score_punctual` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''时间守约评分'' AFTER `score_professional`',
        'SELECT "Column score_punctual already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'score_overall'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `score_overall` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''整体满意度评分'' AFTER `score_punctual`',
        'SELECT "Column score_overall already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'content'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `content` TEXT COMMENT ''回访内容/用户反馈'' AFTER `score_overall`',
        'SELECT "Column content already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'summary'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `summary` TEXT COMMENT ''回访摘要'' AFTER `content`',
        'SELECT "Column summary already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'has_problem'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `has_problem` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''是否有问题 0否 1是'' AFTER `summary`',
        'SELECT "Column has_problem already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'problem_type'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `problem_type` VARCHAR(100) NOT NULL DEFAULT '''' COMMENT ''问题类型'' AFTER `has_problem`',
        'SELECT "Column problem_type already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'problem_desc'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `problem_desc` TEXT COMMENT ''问题描述'' AFTER `problem_type`',
        'SELECT "Column problem_desc already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'problem_status'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `problem_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''问题状态 0未处理 1已处理 2已升级'' AFTER `problem_desc`',
        'SELECT "Column problem_status already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'problem_handle_time'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `problem_handle_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''问题处理时间'' AFTER `problem_status`',
        'SELECT "Column problem_handle_time already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'ticket_id'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `ticket_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''关联工单ID（升级时创建）'' AFTER `problem_handle_time`',
        'SELECT "Column ticket_id already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'retry_count'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `retry_count` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''重试次数'' AFTER `ticket_id`',
        'SELECT "Column retry_count already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'next_retry_time'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `next_retry_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''下次重试时间'' AFTER `retry_count`',
        'SELECT "Column next_retry_time already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'remark'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skipping..."',
    IF(
        @col_exists = 0,
        'ALTER TABLE `la_service_callback` ADD COLUMN `remark` VARCHAR(500) NOT NULL DEFAULT '''' COMMENT ''备注'' AFTER `next_retry_time`',
        'SELECT "Column remark already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @callback_sn_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'callback_sn'
);

SET @sql = IF(
    @table_exists = 0 OR @callback_sn_exists = 0,
    'SELECT "Column callback_sn missing, skip backfill..."',
    'UPDATE `la_service_callback`
        SET `callback_sn` = CONCAT(''CB'', DATE_FORMAT(FROM_UNIXTIME(CASE WHEN `create_time` > 0 THEN `create_time` ELSE UNIX_TIMESTAMP() END), ''%Y%m%d%H%i%s''), LPAD(`id`, 6, ''0''))
      WHERE `callback_sn` = ''''
         OR `callback_sn` IS NULL'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Table la_service_callback not exists, skip compatible data backfill..."',
    'UPDATE `la_service_callback`
        SET `type` = CASE WHEN `type` = 1 AND `callback_type` IN (1, 2, 3) THEN `callback_type` ELSE `type` END,
            `plan_time` = CASE
                WHEN `plan_time` = 0 AND `next_callback_time` > 0 THEN `next_callback_time`
                WHEN `plan_time` = 0 AND `callback_time` > 0 THEN `callback_time`
                ELSE `plan_time`
            END,
            `actual_time` = CASE WHEN `actual_time` = 0 AND `callback_time` > 0 THEN `callback_time` ELSE `actual_time` END,
            `admin_id` = CASE WHEN `admin_id` = 0 AND `callback_admin_id` > 0 THEN `callback_admin_id` ELSE `admin_id` END,
            `score` = CASE WHEN `score` = 0 AND `satisfaction` > 0 THEN `satisfaction` ELSE `score` END,
            `content` = CASE
                WHEN (`content` IS NULL OR `content` = '''') AND `callback_result` IS NOT NULL AND `callback_result` <> '''' THEN `callback_result`
                ELSE `content`
            END'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND INDEX_NAME = 'uk_callback_sn'
);

SET @sql = IF(
    @table_exists = 0 OR @callback_sn_exists = 0,
    'SELECT "Index uk_callback_sn skipped..."',
    IF(
        @index_exists = 0,
        'ALTER TABLE `la_service_callback` ADD UNIQUE KEY `uk_callback_sn` (`callback_sn`)',
        'SELECT "Index uk_callback_sn already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND INDEX_NAME = 'idx_type'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Index idx_type skipped..."',
    IF(
        @index_exists = 0,
        'ALTER TABLE `la_service_callback` ADD KEY `idx_type` (`type`)',
        'SELECT "Index idx_type already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND INDEX_NAME = 'idx_plan_time'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Index idx_plan_time skipped..."',
    IF(
        @index_exists = 0,
        'ALTER TABLE `la_service_callback` ADD KEY `idx_plan_time` (`plan_time`)',
        'SELECT "Index idx_plan_time already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND INDEX_NAME = 'idx_create_time'
);

SET @sql = IF(
    @table_exists = 0,
    'SELECT "Index idx_create_time skipped..."',
    IF(
        @index_exists = 0,
        'ALTER TABLE `la_service_callback` ADD KEY `idx_create_time` (`create_time`)',
        'SELECT "Index idx_create_time already exists, skipping..."'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
