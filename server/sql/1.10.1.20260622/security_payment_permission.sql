ALTER TABLE `la_admin`
    MODIFY COLUMN `password` varchar(255) NOT NULL COMMENT '密码',
    ADD COLUMN `force_password_reset` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否必须重置密码：0-否；1-是' AFTER `multipoint_login`;

UPDATE `la_admin` a
INNER JOIN `la_staff` s ON s.`admin_id` = a.`id`
SET a.`force_password_reset` = 1,
    a.`update_time` = UNIX_TIMESTAMP()
WHERE a.`delete_time` IS NULL;

ALTER TABLE `la_activity_payment`
    DROP INDEX `idx_transaction_id`,
    ADD UNIQUE KEY `uk_transaction_id` (`transaction_id`);

SET @order_list_pid := (
    SELECT `id` FROM `la_system_menu`
    WHERE `perms` = 'order.order/lists'
    ORDER BY `id` ASC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @order_list_pid, 'A', '后台线下收款', '', 0, 'order.order/confirmOfflinePay', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @order_list_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'order.order/confirmOfflinePay');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @order_list_pid, 'A', '支付凭证审核', '', 0, 'order.order/auditPayVoucher', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @order_list_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'order.order/auditPayVoucher');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @order_list_pid, 'A', '订单改档', '', 0, 'order.order/changeSchedule', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @order_list_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'order.order/changeSchedule');

SET @dynamic_list_pid := (
    SELECT `id` FROM `la_system_menu`
    WHERE `perms` = 'growth.dynamic/lists'
    ORDER BY `id` ASC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @dynamic_list_pid, 'A', '活动退款审核', '', 0, 'growth.dynamic/activityRefundAudit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @dynamic_list_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.dynamic/activityRefundAudit');

SET @staff_profile_pid := (
    SELECT `id` FROM `la_system_menu`
    WHERE `perms` IN ('ops.staff/myProfile', 'staff.staff/lists')
    ORDER BY `id` ASC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_profile_pid, 'A', '确认函模板保存', '', 0, 'ops.staff/myScheduleConfirmLetterSave', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_profile_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staff/myScheduleConfirmLetterSave');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_profile_pid, 'A', '确认函模板预览', '', 0, 'ops.staff/myScheduleConfirmLetterPreview', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_profile_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staff/myScheduleConfirmLetterPreview');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_profile_pid, 'A', '确认函模板复制', '', 0, 'ops.staff/myScheduleConfirmLetterCopy', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_profile_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staff/myScheduleConfirmLetterCopy');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_profile_pid, 'A', '确认函模板设为默认', '', 0, 'ops.staff/myScheduleConfirmLetterSetDefault', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_profile_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staff/myScheduleConfirmLetterSetDefault');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_profile_pid, 'A', '确认函模板停用', '', 0, 'ops.staff/myScheduleConfirmLetterDisable', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_profile_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staff/myScheduleConfirmLetterDisable');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_profile_pid, 'A', '确认函生成', '', 0, 'ops.staff/myScheduleConfirmLetterGenerate', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_profile_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staff/myScheduleConfirmLetterGenerate');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_profile_pid, 'A', '确认函历史', '', 0, 'ops.staff/myScheduleConfirmLetterHistory', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_profile_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staff/myScheduleConfirmLetterHistory');
