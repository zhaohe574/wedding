-- =====================================================
-- 站内提醒 crontab 接线
-- 创建时间：2026-03-19
-- 说明：
-- 1. 新增站内服务提醒与暂停到期提醒定时任务
-- 2. 幂等更新 la_dev_crontab
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @upsert_station_reminder_crontab_sql = IF(
    EXISTS(
        SELECT 1
        FROM `la_dev_crontab`
        WHERE `command` = 'send_station_reminders'
          AND `delete_time` IS NULL
    ),
    'UPDATE `la_dev_crontab`
        SET `name` = ''站内提醒发送'',
            `type` = 1,
            `system` = 1,
            `remark` = ''每分钟扫描服务前一天提醒与暂停到期提醒的站内消息'',
            `params` = '''',
            `status` = 1,
            `expression` = ''* * * * *'',
            `update_time` = UNIX_TIMESTAMP()
      WHERE `command` = ''send_station_reminders''
        AND `delete_time` IS NULL',
    'INSERT INTO `la_dev_crontab`
        (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
      VALUES
        (''站内提醒发送'', 1, 1, ''每分钟扫描服务前一天提醒与暂停到期提醒的站内消息'', ''send_station_reminders'', '''', 1, ''* * * * *'', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())'
);
PREPARE stmt FROM @upsert_station_reminder_crontab_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
