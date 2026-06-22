-- Migration: 20260615_activity_registration
-- Owner: backend
-- Scope: activity registration/ticket/payment/refund
-- Rollback: 需要先确认无活动报名有效数据，再删除新增表和新增字段。

ALTER TABLE `la_dynamic`
    ADD COLUMN `activity_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '活动开始时间' AFTER `allow_comment`,
    ADD COLUMN `activity_signup_deadline` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '报名截止时间' AFTER `activity_start_time`,
    ADD COLUMN `activity_signup_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否开启报名：0=否,1=是' AFTER `activity_signup_deadline`,
    ADD COLUMN `activity_total_quota` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '活动总名额：0=不限' AFTER `activity_signup_enabled`,
    ADD COLUMN `activity_registered_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '活动已占用名额' AFTER `activity_total_quota`,
    ADD KEY `idx_activity_time` (`dynamic_type`, `activity_start_time`),
    ADD KEY `idx_activity_signup` (`dynamic_type`, `activity_signup_enabled`, `activity_signup_deadline`),
    ADD KEY `idx_activity_list` (`status`, `dynamic_type`, `is_top`, `create_time`);

CREATE TABLE IF NOT EXISTS `la_activity_ticket` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '活动动态ID',
    `name` VARCHAR(80) NOT NULL DEFAULT '' COMMENT '票种名称',
    `price` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '票价，0=免费',
    `stock` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '票种库存',
    `sold_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已占用数量',
    `sale_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '票种可购买开始时间，0=不限制',
    `sale_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '票种可购买结束时间，0=跟随活动报名截止',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=停用,1=启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_dynamic_id` (`dynamic_id`),
    KEY `idx_sale_time` (`sale_start_time`, `sale_end_time`),
    KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活动票种表';

CREATE TABLE IF NOT EXISTS `la_activity_registration` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '活动动态ID',
    `ticket_id` INT UNSIGNED NOT NULL COMMENT '票种ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `contact_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '联系人',
    `contact_mobile` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '联系电话',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '报名备注',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '报名人数，固定1',
    `ticket_name` VARCHAR(80) NOT NULL DEFAULT '' COMMENT '票种快照',
    `ticket_price` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '票价快照',
    `pay_amount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '应付金额',
    `payment_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '支付流水号',
    `registration_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '报名状态：0=待支付,1=已报名,2=取消审核中,3=已取消,4=退款处理中,5=退款失败',
    `pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态：0=待支付,1=已支付,2=已退款,3=支付失败,4=异常支付',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
    `cancel_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '取消状态：0=未申请,1=待审核,2=已通过,3=已拒绝',
    `cancel_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '取消原因',
    `cancel_reject_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
    `cancel_apply_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '取消申请时间',
    `cancel_audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '取消审核时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_dynamic_id` (`dynamic_id`),
    KEY `idx_ticket_id` (`ticket_id`),
    KEY `idx_user_dynamic` (`user_id`, `dynamic_id`),
    KEY `idx_status` (`registration_status`, `pay_status`, `cancel_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活动报名表';

CREATE TABLE IF NOT EXISTS `la_activity_payment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `payment_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '支付流水号',
    `registration_id` INT UNSIGNED NOT NULL COMMENT '报名ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '活动动态ID',
    `ticket_id` INT UNSIGNED NOT NULL COMMENT '票种ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `pay_way` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式',
    `pay_terminal` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付终端',
    `pay_amount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '支付金额',
    `pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态：0=待支付,1=已支付,2=已退款,3=支付失败',
    `transaction_id` VARCHAR(64) DEFAULT NULL COMMENT '第三方交易号',
    `refund_amount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '已退金额',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款时间',
    `expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付过期时间',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
    `callback_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回调时间',
    `callback_data` TEXT COMMENT '回调数据',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_payment_sn` (`payment_sn`),
    KEY `idx_registration_id` (`registration_id`),
    KEY `idx_dynamic_id` (`dynamic_id`),
    KEY `idx_user_id` (`user_id`),
    UNIQUE KEY `uk_transaction_id` (`transaction_id`),
    KEY `idx_pay_expire` (`pay_status`, `expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活动报名支付表';

CREATE TABLE IF NOT EXISTS `la_activity_refund` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `refund_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '退款单号',
    `registration_id` INT UNSIGNED NOT NULL COMMENT '报名ID',
    `payment_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付ID',
    `dynamic_id` INT UNSIGNED NOT NULL COMMENT '活动动态ID',
    `ticket_id` INT UNSIGNED NOT NULL COMMENT '票种ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `refund_amount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '申请退款金额',
    `actual_refund_amount` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '实际退款金额',
    `refund_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0=待审核,1=审核通过,2=退款中,3=已退款,4=已拒绝,5=退款失败',
    `refund_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因',
    `audit_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '审核备注',
    `third_refund_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方退款号',
    `refund_msg` TEXT COMMENT '退款信息',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款完成时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_refund_sn` (`refund_sn`),
    KEY `idx_registration_id` (`registration_id`),
    KEY `idx_payment_id` (`payment_id`),
    KEY `idx_status` (`refund_status`),
    KEY `idx_dynamic_id` (`dynamic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活动报名退款表';

SET @dynamic_list_pid := (
    SELECT `id` FROM `la_system_menu`
    WHERE `perms` = 'growth.dynamic/lists'
    ORDER BY `id` ASC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @dynamic_list_pid, 'A', '活动报名名单', '', 0, 'growth.dynamic/activityRegistrations', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @dynamic_list_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.dynamic/activityRegistrations');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @dynamic_list_pid, 'A', '活动退款申请', '', 0, 'growth.dynamic/activityRefunds', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @dynamic_list_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.dynamic/activityRefunds');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @dynamic_list_pid, 'A', '活动退款审核', '', 0, 'growth.dynamic/activityRefundAudit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @dynamic_list_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.dynamic/activityRefundAudit');

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @dynamic_list_pid, 'A', '活动报名导出', '', 0, 'growth.dynamic/activityRegistrationExport', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @dynamic_list_pid IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'growth.dynamic/activityRegistrationExport');

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`, `delete_time`)
SELECT '活动报名待支付超时释放', 1, 1, '每分钟扫描待支付活动报名并自动释放票种库存', 'expire_activity_registrations', '', 1, '* * * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'expire_activity_registrations' AND `delete_time` IS NULL);

ALTER TABLE `la_schedule_lock`
    ADD COLUMN `active_key` VARCHAR(64) DEFAULT NULL COMMENT '有效锁唯一键，已释放为空' AFTER `admin_id`;

UPDATE `la_schedule_lock`
SET `active_key` = CONCAT(`schedule_id`, ':', `lock_type`)
WHERE `status` IN (1, 2)
  AND `schedule_id` > 0
  AND `lock_type` > 0;

ALTER TABLE `la_schedule_lock`
    ADD UNIQUE KEY `uk_active_lock` (`active_key`);
