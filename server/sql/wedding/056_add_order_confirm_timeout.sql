-- =====================================================
-- 订单服务人员确认超时处理
-- 创建时间：2026-04-02
-- 说明：
-- 1. 扩展 la_order.confirm_deadline_time
-- 2. 新增 transaction 配置项
-- 3. 新增服务人员确认超时自动处理定时任务
-- 4. 补充后台页面调用所需权限节点
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @add_confirm_deadline_time_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'confirm_deadline_time'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `confirm_deadline_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''确认截止时间'' AFTER `pay_deadline_time`'
);
PREPARE stmt FROM @add_confirm_deadline_time_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_confirm_deadline_index_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND INDEX_NAME = 'idx_confirm_deadline_time'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD KEY `idx_confirm_deadline_time` (`order_status`, `confirm_deadline_time`)'
);
PREPARE stmt FROM @add_confirm_deadline_index_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'transaction', 'staff_confirm_timeout_enabled', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'transaction' AND `name` = 'staff_confirm_timeout_enabled'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'transaction', 'staff_confirm_timeout_action', 'cancel', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'transaction' AND `name` = 'staff_confirm_timeout_action'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'transaction', 'staff_confirm_timeout_minutes', '60', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'transaction' AND `name` = 'staff_confirm_timeout_minutes'
);

SET @upsert_staff_confirm_crontab_sql = IF(
    EXISTS(
        SELECT 1
        FROM `la_dev_crontab`
        WHERE `command` = 'handle_pending_confirm_orders'
          AND `delete_time` IS NULL
    ),
    'UPDATE `la_dev_crontab`
        SET `name` = ''服务人员确认超时自动处理'',
            `type` = 1,
            `system` = 1,
            `remark` = ''每分钟扫描待确认订单并按配置自动取消或自动同意'',
            `params` = '''',
            `status` = 1,
            `expression` = ''* * * * *'',
            `update_time` = UNIX_TIMESTAMP()
      WHERE `command` = ''handle_pending_confirm_orders''
        AND `delete_time` IS NULL',
    'INSERT INTO `la_dev_crontab`
        (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
      VALUES
        (''服务人员确认超时自动处理'', 1, 1, ''每分钟扫描待确认订单并按配置自动取消或自动同意'', ''handle_pending_confirm_orders'', '''', 1, ''* * * * *'', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())'
);
PREPARE stmt FROM @upsert_staff_confirm_crontab_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT m.id, 'A', '获取订单超时设置', '', 0, 'setting.transaction_settings/getConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` m
WHERE m.perms = 'setting.feature_switch/getConfig'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'setting.transaction_settings/getConfig'
  );

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT m.id, 'A', '保存订单超时设置', '', 0, 'setting.transaction_settings/setConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` m
WHERE m.perms = 'setting.feature_switch/getConfig'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'setting.transaction_settings/setConfig'
  );

SET FOREIGN_KEY_CHECKS = 1;
