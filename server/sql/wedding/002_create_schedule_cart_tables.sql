-- ============================================================
-- 婚庆服务预约系统 - 第二阶段数据表
-- 档期管理 + 购物车
-- 创建日期: 2026-01-19
-- ============================================================

-- -----------------------------------------------------------
-- 1. 档期规则表 (la_schedule_rule)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_schedule_rule`;
CREATE TABLE `la_schedule_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '工作人员ID（0=全局规则）',
    `advance_days` INT UNSIGNED NOT NULL DEFAULT 3 COMMENT '提前预约天数',
    `max_orders_per_day` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '单日最大接单数',
    `interval_hours` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单间隔时间（小时）',
    `work_start_time` VARCHAR(10) NOT NULL DEFAULT '09:00' COMMENT '工作开始时间',
    `work_end_time` VARCHAR(10) NOT NULL DEFAULT '18:00' COMMENT '工作结束时间',
    `rest_days` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '休息日（逗号分隔，0=周日,1=周一...）',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期规则表';

-- -----------------------------------------------------------
-- 2. 档期表 (la_schedule)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_schedule`;
CREATE TABLE `la_schedule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `schedule_date` DATE NOT NULL COMMENT '档期日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=不可用,1=可预约,2=已预约,3=已锁定,4=内部预留',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `lock_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定类型：0=正常,1=VIP锁定,2=内部预留',
    `lock_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定用户ID',
    `lock_expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定到期时间',
    `lock_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '锁定/预留原因',
    `queue_lock_until` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '候补锁档截止时间',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '当日价格（0=使用默认价格）',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `version` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '乐观锁版本号',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_date_slot` (`staff_id`, `schedule_date`, `time_slot`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_status` (`status`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期表';

-- -----------------------------------------------------------
-- 3. 档期锁定记录表 (la_schedule_lock)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_schedule_lock`;
CREATE TABLE `la_schedule_lock` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `schedule_id` INT UNSIGNED NOT NULL COMMENT '档期ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定用户ID',
    `lock_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '锁定类型：1=VIP锁定,2=内部预留,3=临时锁定',
    `lock_start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定开始时间',
    `lock_end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定结束时间',
    `lock_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '锁定原因',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=已释放,1=锁定中,2=已转订单',
    `release_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '释放时间',
    `release_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '释放原因',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_schedule_id` (`schedule_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期锁定记录表';

-- -----------------------------------------------------------
-- 4. 档期共享表 (la_schedule_share)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_schedule_share`;
CREATE TABLE `la_schedule_share` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `group_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '组合名称',
    `staff_ids` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '工作人员ID列表（逗号分隔）',
    `share_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '共享类型：1=档期同步,2=组合套餐',
    `discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '组合折扣率（如95表示95折）',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='档期共享表';

-- -----------------------------------------------------------
-- 5. 购物车表 (la_cart)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_cart`;
CREATE TABLE `la_cart` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID（0=仅预约人员）',
    `schedule_date` DATE NOT NULL COMMENT '预约日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `is_selected` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否选中：0=否,1=是',
    `share_code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '分享码',
    `share_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分享来源用户ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='购物车表';

-- -----------------------------------------------------------
-- 6. 购物车方案表 (la_cart_plan)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_cart_plan`;
CREATE TABLE `la_cart_plan` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `plan_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '方案名称',
    `cart_ids` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '购物车项ID列表（JSON）',
    `items_snapshot` TEXT NULL COMMENT '方案明细快照（JSON）',
    `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '方案总价',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '方案备注',
    `share_code` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '分享码',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认方案：0=否,1=是',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_share_code` (`share_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='购物车方案表';

-- -----------------------------------------------------------
-- 7. 候补订单表 (la_waitlist)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_waitlist`;
CREATE TABLE `la_waitlist` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `staff_id` INT UNSIGNED NOT NULL COMMENT '工作人员ID',
    `schedule_date` DATE NOT NULL COMMENT '预约日期',
    `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `notify_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '通知状态：0=未通知,1=已通知,2=已下单,3=已过期',
    `notify_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '通知时间',
    `expire_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_schedule_date` (`schedule_date`),
    KEY `idx_notify_status` (`notify_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='候补订单表';

-- -----------------------------------------------------------
-- 8. 黄历/吉日表 (la_calendar_event)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_calendar_event`;
CREATE TABLE `la_calendar_event` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `event_date` DATE NOT NULL COMMENT '日期',
    `lunar_date` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '农历日期',
    `is_lucky_day` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否吉日：0=否,1=是',
    `lucky_events` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '宜（如：结婚,订婚）',
    `unlucky_events` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '忌',
    `is_holiday` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否节假日：0=否,1=是',
    `holiday_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '节假日名称',
    `congestion_level` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '拥堵等级：0=未知,1=低,2=中,3=高',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_event_date` (`event_date`),
    KEY `idx_is_lucky_day` (`is_lucky_day`),
    KEY `idx_is_holiday` (`is_holiday`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='黄历/吉日表';

-- -----------------------------------------------------------
-- 初始化数据
-- -----------------------------------------------------------

-- 全局档期规则
INSERT INTO `la_schedule_rule` (`staff_id`, `advance_days`, `max_orders_per_day`, `interval_hours`, `work_start_time`, `work_end_time`, `rest_days`, `is_enabled`, `create_time`, `update_time`) VALUES
(0, 3, 1, 0, '08:00', '20:00', '', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 示例吉日数据（2026年部分吉日）
INSERT INTO `la_calendar_event` (`event_date`, `lunar_date`, `is_lucky_day`, `lucky_events`, `unlucky_events`, `is_holiday`, `holiday_name`, `congestion_level`, `create_time`, `update_time`) VALUES
('2026-01-01', '十一月十二', 0, '', '', 1, '元旦', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-02-17', '正月初一', 1, '祈福,嫁娶,订盟', '动土,破土', 1, '春节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-05-01', '三月十五', 1, '嫁娶,订盟,纳采', '开市,动土', 1, '劳动节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-05-20', '四月初四', 1, '嫁娶,订盟,纳采', '安葬,破土', 0, '', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('2026-10-01', '八月廿一', 1, '嫁娶,祈福,订盟', '安葬,动土', 1, '国庆节', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
