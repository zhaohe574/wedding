-- =====================================================
-- 预约订单退款查询定时任务
-- 创建时间：2026-04-26
-- 说明：初始化微信退款状态补偿查询任务
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @upsert_query_refund_crontab_sql = IF(
    EXISTS(
        SELECT 1
        FROM `la_dev_crontab`
        WHERE `command` = 'query_refund'
          AND `delete_time` IS NULL
    ),
    'UPDATE `la_dev_crontab`
        SET `name` = ''预约订单退款查询'',
            `type` = 1,
            `system` = 1,
            `remark` = ''每分钟查询处理中微信退款并同步订单退款状态'',
            `params` = '''',
            `status` = 1,
            `expression` = ''* * * * *'',
            `update_time` = UNIX_TIMESTAMP()
      WHERE `command` = ''query_refund''
        AND `delete_time` IS NULL',
    'INSERT INTO `la_dev_crontab`
        (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
      VALUES
        (''预约订单退款查询'', 1, 1, ''每分钟查询处理中微信退款并同步订单退款状态'', ''query_refund'', '''', 1, ''* * * * *'', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())'
);
PREPARE stmt FROM @upsert_query_refund_crontab_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
