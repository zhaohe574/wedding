-- =============================================
-- 远端数据库结构升级脚本
-- 目标库：47.93.32.241:3306 / wedding
-- 生成时间：2026-03-19
-- 说明：
-- 1. 仅同步本地代码当前依赖的通用结构与菜单权限
-- 2. 不同步本地环境中的业务配置数据（如城市池、套餐地区价格、套餐附加服务具体记录）
-- 3. 脚本按幂等方式编写，可用于远端现网库补齐缺失结构
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- Part 1: 收敛旧字段
-- =============================================================================

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_category'
          AND INDEX_NAME = 'idx_level'
    ),
    'ALTER TABLE `la_service_category` DROP INDEX `idx_level`',
    'SELECT 1'
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
          AND COLUMN_NAME = 'level'
    ),
    'ALTER TABLE `la_service_category` DROP COLUMN `level`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff'
          AND INDEX_NAME = 'idx_price'
    ),
    'ALTER TABLE `la_staff` DROP INDEX `idx_price`',
    'SELECT 1'
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
          AND COLUMN_NAME = 'price'
    ),
    'ALTER TABLE `la_staff` DROP COLUMN `price`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Part 2: 员工作品表补齐字段
-- =============================================================================

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff_work'
          AND COLUMN_NAME = 'type'
    ),
    'SELECT 1',
    'ALTER TABLE `la_staff_work` ADD COLUMN `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''作品类型:1-图片,2-视频'' AFTER `title`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_staff_work'
          AND COLUMN_NAME = 'is_cover'
    ),
    'SELECT 1',
    'ALTER TABLE `la_staff_work` ADD COLUMN `is_cover` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''是否封面:0-否,1-是'' AFTER `is_show`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Part 3: 服务回访表结构修复
-- =============================================================================

SET @service_callback_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
);
SET @service_callback_count = IF(
    @service_callback_exists = 0,
    0,
    (SELECT COUNT(*) FROM `la_service_callback`)
);
SET @service_callback_has_new = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_service_callback'
      AND COLUMN_NAME = 'callback_sn'
);
SET @sql = IF(
    @service_callback_exists = 0,
    'SELECT 1',
    IF(
        @service_callback_count = 0 AND @service_callback_has_new = 0,
        'DROP TABLE `la_service_callback`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `la_service_callback` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回访ID',
    `callback_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '回访编号',
    `order_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `method` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访方式 1电话 2短信 3微信 4小程序问卷',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待回访 1已回访 2无法联系 3已取消',
    `plan_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '计划回访时间',
    `actual_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '实际回访时间',
    `admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访人ID',
    `duration` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访时长（秒）',
    `score` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评 1-5星',
    `score_service` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务态度评分',
    `score_professional` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '专业水平评分',
    `score_punctual` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间守约评分',
    `score_overall` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '整体满意度评分',
    `content` TEXT COMMENT '回访内容/用户反馈',
    `summary` TEXT COMMENT '回访摘要',
    `has_problem` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否有问题 0否 1是',
    `problem_type` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '问题类型',
    `problem_desc` TEXT COMMENT '问题描述',
    `problem_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问题状态 0未处理 1已处理 2已升级',
    `problem_handle_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问题处理时间',
    `ticket_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联工单ID（升级时创建）',
    `retry_count` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
    `next_retry_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次重试时间',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_callback_sn` (`callback_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`),
    KEY `idx_plan_time` (`plan_time`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务回访表';

SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_sn'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `callback_sn` VARCHAR(32) NOT NULL DEFAULT '''' COMMENT ''回访编号'' AFTER `id`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'type'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''回访类型 1服务前 2服务中 3服务后'' AFTER `staff_id`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'method'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `method` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''回访方式 1电话 2短信 3微信 4小程序问卷'' AFTER `type`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'plan_time'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `plan_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''计划回访时间'' AFTER `status`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'actual_time'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `actual_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''实际回访时间'' AFTER `plan_time`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'admin_id'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''回访人ID'' AFTER `actual_time`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'duration'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `duration` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''回访时长（秒）'' AFTER `admin_id`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'score'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `score` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''满意度评分 0未评 1-5星'' AFTER `duration`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'score_service'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `score_service` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''服务态度评分'' AFTER `score`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'score_professional'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `score_professional` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''专业水平评分'' AFTER `score_service`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'score_punctual'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `score_punctual` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''时间守约评分'' AFTER `score_professional`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'score_overall'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `score_overall` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''整体满意度评分'' AFTER `score_punctual`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'content'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `content` TEXT COMMENT ''回访内容/用户反馈'' AFTER `score_overall`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'summary'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `summary` TEXT COMMENT ''回访摘要'' AFTER `content`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'has_problem'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `has_problem` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''是否有问题 0否 1是'' AFTER `summary`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'problem_type'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `problem_type` VARCHAR(100) NOT NULL DEFAULT '''' COMMENT ''问题类型'' AFTER `has_problem`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'problem_desc'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `problem_desc` TEXT COMMENT ''问题描述'' AFTER `problem_type`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'problem_status'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `problem_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''问题状态 0未处理 1已处理 2已升级'' AFTER `problem_desc`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'problem_handle_time'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `problem_handle_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''问题处理时间'' AFTER `problem_status`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'ticket_id'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `ticket_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''关联工单ID（升级时创建）'' AFTER `problem_handle_time`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'retry_count'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `retry_count` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''重试次数'' AFTER `ticket_id`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'next_retry_time'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `next_retry_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''下次重试时间'' AFTER `retry_count`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'remark'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `remark` VARCHAR(500) NOT NULL DEFAULT '''' COMMENT ''备注'' AFTER `next_retry_time`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'delete_time'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD COLUMN `delete_time` INT(11) UNSIGNED NULL DEFAULT NULL COMMENT ''删除时间'' AFTER `update_time`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `la_service_callback`
SET `callback_sn` = CASE
    WHEN `callback_sn` <> '' THEN `callback_sn`
    ELSE CONCAT(
        'CB',
        DATE_FORMAT(FROM_UNIXTIME(IF(`create_time` > 0, `create_time`, UNIX_TIMESTAMP())), '%Y%m%d'),
        LPAD(`id`, 6, '0')
    )
END
WHERE `callback_sn` = '';

SET @has_callback_type = EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_type');
SET @has_callback_time = EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_time');
SET @has_callback_admin_id = EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_admin_id');
SET @has_callback_result = EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_result');
SET @has_satisfaction = EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'satisfaction');
SET @has_satisfaction_remark = EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'satisfaction_remark');
SET @has_next_callback_time = EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'next_callback_time');
SET @sql = IF(
    @has_callback_type = 0 AND @has_callback_time = 0 AND @has_callback_admin_id = 0 AND @has_callback_result = 0 AND @has_satisfaction = 0 AND @has_satisfaction_remark = 0 AND @has_next_callback_time = 0,
    'SELECT 1',
    'UPDATE `la_service_callback` SET `type` = IF(@has_callback_type = 1, IFNULL(`callback_type`, `type`), `type`), `actual_time` = IF(@has_callback_time = 1, IFNULL(`callback_time`, `actual_time`), `actual_time`), `admin_id` = IF(@has_callback_admin_id = 1, IFNULL(`callback_admin_id`, `admin_id`), `admin_id`), `content` = IF(@has_callback_result = 1 AND (`content` IS NULL OR `content` = ''''), IFNULL(`callback_result`, ''''), `content`), `score` = IF(@has_satisfaction = 1, IFNULL(`satisfaction`, `score`), `score`), `summary` = IF(@has_satisfaction_remark = 1 AND (`summary` IS NULL OR `summary` = ''''), IFNULL(`satisfaction_remark`, ''''), `summary`), `plan_time` = IF(@has_next_callback_time = 1 AND `plan_time` = 0, IFNULL(`next_callback_time`, `plan_time`), IF(`plan_time` = 0, `create_time`, `plan_time`)), `status` = IF(@has_callback_type = 1, CASE `status` WHEN 2 THEN 3 ELSE `status` END, `status`)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND INDEX_NAME = 'uk_callback_sn'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD UNIQUE KEY `uk_callback_sn` (`callback_sn`)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND INDEX_NAME = 'idx_type'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD KEY `idx_type` (`type`)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND INDEX_NAME = 'idx_plan_time'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD KEY `idx_plan_time` (`plan_time`)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND INDEX_NAME = 'idx_create_time'), 'SELECT 1', 'ALTER TABLE `la_service_callback` ADD KEY `idx_create_time` (`create_time`)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND INDEX_NAME = 'idx_callback_time'), 'ALTER TABLE `la_service_callback` DROP INDEX `idx_callback_time`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'order_item_id'), 'ALTER TABLE `la_service_callback` DROP COLUMN `order_item_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_type'), 'ALTER TABLE `la_service_callback` DROP COLUMN `callback_type`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_time'), 'ALTER TABLE `la_service_callback` DROP COLUMN `callback_time`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_admin_id'), 'ALTER TABLE `la_service_callback` DROP COLUMN `callback_admin_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'callback_result'), 'ALTER TABLE `la_service_callback` DROP COLUMN `callback_result`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'satisfaction'), 'ALTER TABLE `la_service_callback` DROP COLUMN `satisfaction`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'satisfaction_remark'), 'ALTER TABLE `la_service_callback` DROP COLUMN `satisfaction_remark`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_service_callback' AND COLUMN_NAME = 'next_callback_time'), 'ALTER TABLE `la_service_callback` DROP COLUMN `next_callback_time`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Part 4: 附加服务相关结构与菜单
-- =============================================================================

CREATE TABLE IF NOT EXISTS `la_service_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属服务人员ID',
    `category_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属服务分类ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '附加服务名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '售价',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
    `image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '图片',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '描述',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `is_show` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否上架：0=下架，1=上架',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_is_show` (`is_show`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='附加服务配置表';

CREATE TABLE IF NOT EXISTS `la_order_item_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '主订单项ID',
    `addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
    `addon_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '附加服务快照名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '快照单价',
    `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量，固定为1',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1=生效中，2=已移除',
    `create_source` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建来源：1=下单，2=变更',
    `create_change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建变更单ID',
    `remove_change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '移除变更单ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_order_item_id` (`order_item_id`),
    KEY `idx_addon_id` (`addon_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单附加服务快照表';

CREATE TABLE IF NOT EXISTS `la_order_change_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更单ID',
    `order_item_addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单附加服务快照ID',
    `addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
    `addon_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '附加服务名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
    `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_change_id` (`change_id`),
    KEY `idx_order_item_addon_id` (`order_item_addon_id`),
    KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单变更附加服务明细表';

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'addon_amount'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `addon_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT ''附加服务金额'' AFTER `total_amount`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `la_order_change`
    MODIFY COLUMN `change_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '变更类型：1=改期，2=换人，3=加项，4=附加服务变更';

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order_change'
          AND COLUMN_NAME = 'addon_action'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order_change` ADD COLUMN `addon_action` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''附加服务动作：1=新增，2=移除'' AFTER `change_type`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '附加服务', '', 75, 'ops.addon/lists', 'addon', 'service/addon/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/lists')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '详情', '', 0, 'ops.addon/detail', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/detail')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '新增', '', 0, 'ops.addon/add', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/add')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '编辑', '', 0, 'ops.addon/edit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/edit')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '删除', '', 0, 'ops.addon/delete', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/delete')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '状态切换', '', 0, 'ops.addon/changeStatus', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/changeStatus')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '全部', '', 0, 'ops.addon/all', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/all')
LIMIT 1;

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT service_role.`role_id`, addon_menu.`id`
FROM `la_system_role_menu` service_role
JOIN `la_system_menu` service_root
  ON service_root.`id` = service_role.`menu_id`
 AND service_root.`type` = 'M'
 AND service_root.`paths` = 'service'
JOIN `la_system_menu` addon_menu
  ON addon_menu.`perms` = 'ops.addon/lists'
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_system_role_menu` exists_map
    WHERE exists_map.`role_id` = service_role.`role_id`
      AND exists_map.`menu_id` = addon_menu.`id`
);

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT service_role.`role_id`, addon_action.`id`
FROM `la_system_role_menu` service_role
JOIN `la_system_menu` service_root
  ON service_root.`id` = service_role.`menu_id`
 AND service_root.`type` = 'M'
 AND service_root.`paths` = 'service'
JOIN `la_system_menu` addon_menu
  ON addon_menu.`perms` = 'ops.addon/lists'
JOIN `la_system_menu` addon_action
  ON addon_action.`pid` = addon_menu.`id`
 AND addon_action.`type` = 'A'
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_system_role_menu` exists_map
    WHERE exists_map.`role_id` = service_role.`role_id`
      AND exists_map.`menu_id` = addon_action.`id`
);

-- =============================================================================
-- Part 5: 套餐附加服务关联表
-- =============================================================================

CREATE TABLE IF NOT EXISTS `la_service_package_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_package_addon` (`package_id`, `addon_id`),
    KEY `idx_package_id` (`package_id`),
    KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套餐附加服务关联表';

-- =============================================================================
-- Part 6: 地区能力与套餐地区定价
-- =============================================================================

CREATE TABLE IF NOT EXISTS `la_service_city_pool` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `province_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '省编码',
    `province_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '省名称',
    `city_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '市编码',
    `city_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '市名称',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=停用，1=启用',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_city_code` (`city_code`),
    KEY `idx_status_sort` (`status`, `sort`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='可接单城市池';

CREATE TABLE IF NOT EXISTS `la_service_package_region_price` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '人员ID',
    `region_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '地区层级：1=省，2=市，3=区县',
    `province_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '省编码',
    `province_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '省名称',
    `city_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '市编码',
    `city_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '市名称',
    `district_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '区县编码',
    `district_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '区县名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '地区售价',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_package_region` (`package_id`, `region_level`, `province_code`, `city_code`, `district_code`),
    KEY `idx_staff_package` (`staff_id`, `package_id`),
    KEY `idx_city_district` (`city_code`, `district_code`),
    KEY `idx_province_city_district` (`province_code`, `city_code`, `district_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='套餐地区价格表';

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_province_code'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_province_code` VARCHAR(12) NOT NULL DEFAULT '''' COMMENT ''服务省编码'' AFTER `service_address`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_province'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_province` VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''服务省'' AFTER `service_province_code`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_city_code'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_city_code` VARCHAR(12) NOT NULL DEFAULT '''' COMMENT ''服务市编码'' AFTER `service_province`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_city'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_city` VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''服务市'' AFTER `service_city_code`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_district_code'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_district_code` VARCHAR(12) NOT NULL DEFAULT '''' COMMENT ''服务区县编码'' AFTER `service_city`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_district'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_district` VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''服务区县'' AFTER `service_district_code`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '服务地区', '', 74, 'ops.region/lists', 'region', 'service/region/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/lists')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '城市选项', '', 0, 'ops.region/cityOptions', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/cityOptions')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '启用城市选项', '', 0, 'ops.region/enabledCityOptions', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/enabledCityOptions')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '区县选项', '', 0, 'ops.region/districtOptions', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/districtOptions')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '新增', '', 0, 'ops.region/add', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/add')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '编辑', '', 0, 'ops.region/edit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/edit')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '删除', '', 0, 'ops.region/delete', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/delete')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '状态切换', '', 0, 'ops.region/changeStatus', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/changeStatus')
LIMIT 1;

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT service_role.`role_id`, region_menu.`id`
FROM `la_system_role_menu` service_role
JOIN `la_system_menu` service_root
  ON service_root.`id` = service_role.`menu_id`
 AND service_root.`type` = 'M'
 AND service_root.`paths` = 'service'
JOIN `la_system_menu` region_menu
  ON region_menu.`perms` = 'ops.region/lists'
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_system_role_menu` exists_map
    WHERE exists_map.`role_id` = service_role.`role_id`
      AND exists_map.`menu_id` = region_menu.`id`
);

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT service_role.`role_id`, region_action.`id`
FROM `la_system_role_menu` service_role
JOIN `la_system_menu` service_root
  ON service_root.`id` = service_role.`menu_id`
 AND service_root.`type` = 'M'
 AND service_root.`paths` = 'service'
JOIN `la_system_menu` region_menu
  ON region_menu.`perms` = 'ops.region/lists'
JOIN `la_system_menu` region_action
  ON region_action.`pid` = region_menu.`id`
 AND region_action.`type` = 'A'
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_system_role_menu` exists_map
    WHERE exists_map.`role_id` = service_role.`role_id`
      AND exists_map.`menu_id` = region_action.`id`
);

ALTER TABLE `la_service_package_region_price`
    MODIFY COLUMN `region_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '地区层级：1=省，2=市，3=区县';

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_package_region_price'
          AND INDEX_NAME = 'uniq_package_region'
    ),
    'ALTER TABLE `la_service_package_region_price` DROP INDEX `uniq_package_region`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_package_region_price'
          AND INDEX_NAME = 'uniq_package_region'
    ),
    'SELECT 1',
    'ALTER TABLE `la_service_package_region_price` ADD UNIQUE KEY `uniq_package_region` (`package_id`, `region_level`, `province_code`, `city_code`, `district_code`)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_service_package_region_price'
          AND INDEX_NAME = 'idx_province_city_district'
    ),
    'SELECT 1',
    'ALTER TABLE `la_service_package_region_price` ADD KEY `idx_province_city_district` (`province_code`, `city_code`, `district_code`)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
