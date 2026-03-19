-- =====================================================
-- 订单超时未支付自动取消
-- 创建时间：2026-03-19
-- 说明：
-- 1. 扩展 la_order.pay_deadline_time
-- 2. 回填历史待支付订单截止时间
-- 3. 新增超时未支付订单自动取消定时任务
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @add_pay_deadline_time_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'pay_deadline_time'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `pay_deadline_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''支付截止时间'' AFTER `pay_time`'
);
PREPARE stmt FROM @add_pay_deadline_time_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_pay_deadline_index_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND INDEX_NAME = 'idx_pay_deadline_time'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD KEY `idx_pay_deadline_time` (`order_status`, `pay_deadline_time`)'
);
PREPARE stmt FROM @add_pay_deadline_index_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @cancel_enabled = COALESCE((
    SELECT CAST(`value` AS UNSIGNED)
    FROM `la_config`
    WHERE `type` = 'transaction'
      AND `name` = 'cancel_unpaid_orders'
    LIMIT 1
), 1);

SET @cancel_minutes = COALESCE((
    SELECT CAST(`value` AS UNSIGNED)
    FROM `la_config`
    WHERE `type` = 'transaction'
      AND `name` = 'cancel_unpaid_orders_times'
    LIMIT 1
), 30);

UPDATE `la_order` o
LEFT JOIN (
    SELECT `order_id`, MAX(`create_time`) AS `pending_pay_time`
    FROM `la_order_log`
    WHERE `after_status` = 1
    GROUP BY `order_id`
) log ON log.order_id = o.id
SET o.`pay_deadline_time` = CASE
    WHEN @cancel_enabled <> 1 THEN 0
    WHEN o.`order_status` <> 1 THEN 0
    WHEN o.`pay_status` = 1 THEN 0
    WHEN o.`deposit_amount` > 0 AND o.`deposit_paid` = 1 THEN 0
    ELSE COALESCE(log.`pending_pay_time`, o.`create_time`) + (@cancel_minutes * 60)
END;

SET @upsert_cancel_unpaid_crontab_sql = IF(
    EXISTS(
        SELECT 1
        FROM `la_dev_crontab`
        WHERE `command` = 'cancel_unpaid_orders'
          AND `delete_time` IS NULL
    ),
    'UPDATE `la_dev_crontab`
        SET `name` = ''超时未支付订单自动取消'',
            `type` = 1,
            `system` = 1,
            `remark` = ''每分钟扫描待支付首笔订单并自动取消超时单'',
            `params` = '''',
            `status` = 1,
            `expression` = ''* * * * *'',
            `update_time` = UNIX_TIMESTAMP()
      WHERE `command` = ''cancel_unpaid_orders''
        AND `delete_time` IS NULL',
    'INSERT INTO `la_dev_crontab`
        (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
      VALUES
        (''超时未支付订单自动取消'', 1, 1, ''每分钟扫描待支付首笔订单并自动取消超时单'', ''cancel_unpaid_orders'', '''', 1, ''* * * * *'', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())'
);
PREPARE stmt FROM @upsert_cancel_unpaid_crontab_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
