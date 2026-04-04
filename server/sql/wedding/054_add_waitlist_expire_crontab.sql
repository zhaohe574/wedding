-- =====================================================
-- 候补超过预约日期自动失效
-- 创建时间：2026-04-02
-- 说明：
-- 1. 新增候补超期自动失效定时任务
-- 2. 幂等更新 la_dev_crontab
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @upsert_expire_waitlists_crontab_sql = IF(
    EXISTS(
        SELECT 1
        FROM `la_dev_crontab`
        WHERE `command` = 'expire_waitlists'
          AND `delete_time` IS NULL
    ),
    'UPDATE `la_dev_crontab`
        SET `name` = ''候补超期自动失效'',
            `type` = 1,
            `system` = 1,
            `remark` = ''每天扫描预约日期已过的候补并自动标记为已过期'',
            `params` = '''',
            `status` = 1,
            `expression` = ''10 0 * * *'',
            `update_time` = UNIX_TIMESTAMP()
      WHERE `command` = ''expire_waitlists''
        AND `delete_time` IS NULL',
    'INSERT INTO `la_dev_crontab`
        (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
      VALUES
        (''候补超期自动失效'', 1, 1, ''每天扫描预约日期已过的候补并自动标记为已过期'', ''expire_waitlists'', '''', 1, ''10 0 * * *'', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())'
);
PREPARE stmt FROM @upsert_expire_waitlists_crontab_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
