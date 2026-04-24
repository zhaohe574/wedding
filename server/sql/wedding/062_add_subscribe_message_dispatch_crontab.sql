-- =====================================================
-- 订阅消息派发定时任务与发送队列字段
-- 创建时间：2026-04-20
-- 说明：
-- 1. 为订阅消息发送日志补充计划发送时间字段
-- 2. 新增待发送扫描索引
-- 3. 初始化订阅消息派发定时任务
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @add_subscribe_log_planned_send_time_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_subscribe_message_log'
          AND COLUMN_NAME = 'planned_send_time'
    ),
    'SELECT 1',
    'ALTER TABLE `la_subscribe_message_log` ADD COLUMN `planned_send_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''计划发送时间'' AFTER `miniprogram_state`'
);
PREPARE stmt FROM @add_subscribe_log_planned_send_time_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_subscribe_log_send_plan_index_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_subscribe_message_log'
          AND INDEX_NAME = 'idx_send_plan'
    ),
    'SELECT 1',
    'ALTER TABLE `la_subscribe_message_log` ADD KEY `idx_send_plan` (`send_status`, `planned_send_time`)'
);
PREPARE stmt FROM @add_subscribe_log_send_plan_index_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `la_subscribe_message_log`
SET `planned_send_time` = `create_time`
WHERE `send_status` = 0
  AND `planned_send_time` = 0;

SET @upsert_send_subscribe_messages_crontab_sql = IF(
    EXISTS(
        SELECT 1
        FROM `la_dev_crontab`
        WHERE `command` = 'send_subscribe_messages'
          AND `delete_time` IS NULL
    ),
    'UPDATE `la_dev_crontab`
        SET `name` = ''订阅消息派发'',
            `type` = 1,
            `system` = 1,
            `remark` = ''每分钟扫描并派发到期的小程序订阅消息'',
            `params` = '''',
            `status` = 1,
            `expression` = ''* * * * *'',
            `update_time` = UNIX_TIMESTAMP()
      WHERE `command` = ''send_subscribe_messages''
        AND `delete_time` IS NULL',
    'INSERT INTO `la_dev_crontab`
        (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
      VALUES
        (''订阅消息派发'', 1, 1, ''每分钟扫描并派发到期的小程序订阅消息'', ''send_subscribe_messages'', '''', 1, ''* * * * *'', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())'
);
PREPARE stmt FROM @upsert_send_subscribe_messages_crontab_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
