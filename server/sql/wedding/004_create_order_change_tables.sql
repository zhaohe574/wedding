-- =============================================
-- 婚庆服务预约系统 - 订单高级功能表结构
-- Phase 4: 订单变更、转让、暂停功能
-- 创建时间: 2026-01-19
-- =============================================

-- =============================================
-- 1. 订单变更申请表
-- 支持改期、换人、加项三种变更类型
-- =============================================
DROP TABLE IF EXISTS `la_order_change`;
CREATE TABLE `la_order_change` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `change_sn` VARCHAR(32) NOT NULL COMMENT '变更单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `change_type` TINYINT UNSIGNED NOT NULL COMMENT '变更类型：1=改期,2=换人,3=加项',
    `change_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更状态：0=待审核,1=审核通过,2=审核拒绝,3=已执行,4=已取消',
    
    -- 改期相关字段
    `old_service_date` DATE DEFAULT NULL COMMENT '原服务日期',
    `new_service_date` DATE DEFAULT NULL COMMENT '新服务日期',
    `old_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '原时间段：0=全天,1=早礼,2=午宴,3=晚宴',
    `new_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '新时间段',
    
    -- 换人相关字段
    `order_item_id` INT UNSIGNED DEFAULT 0 COMMENT '订单项ID（换人/加项用）',
    `old_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '原工作人员ID',
    `new_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '新工作人员ID',
    `old_staff_name` VARCHAR(50) DEFAULT '' COMMENT '原人员姓名(冗余)',
    `new_staff_name` VARCHAR(50) DEFAULT '' COMMENT '新人员姓名(冗余)',
    `old_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '原档期ID',
    `new_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '新档期ID',
    `old_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '原价格',
    `new_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '新价格',
    `price_diff` DECIMAL(10,2) DEFAULT 0.00 COMMENT '差价(正数补付,负数退款)',
    `diff_paid` TINYINT UNSIGNED DEFAULT 0 COMMENT '差价是否已处理：0=否,1=是',
    `diff_payment_id` INT UNSIGNED DEFAULT 0 COMMENT '差价支付记录ID',
    `diff_refund_id` INT UNSIGNED DEFAULT 0 COMMENT '差价退款记录ID',
    
    -- 加项相关字段
    `add_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '新增工作人员ID',
    `add_package_id` INT UNSIGNED DEFAULT 0 COMMENT '新增套餐ID',
    `add_staff_name` VARCHAR(50) DEFAULT '' COMMENT '新增人员姓名',
    `add_package_name` VARCHAR(100) DEFAULT '' COMMENT '新增套餐名称',
    `add_service_date` DATE DEFAULT NULL COMMENT '新增服务日期',
    `add_time_slot` TINYINT UNSIGNED DEFAULT 0 COMMENT '新增时间段',
    `add_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '新增价格',
    `add_schedule_id` INT UNSIGNED DEFAULT 0 COMMENT '新增档期ID',
    `add_order_item_id` INT UNSIGNED DEFAULT 0 COMMENT '新增订单项ID',
    
    -- 审核相关
    `apply_reason` VARCHAR(500) DEFAULT '' COMMENT '申请原因',
    `user_remark` VARCHAR(500) DEFAULT '' COMMENT '用户备注',
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    -- 执行相关
    `execute_time` INT UNSIGNED DEFAULT 0 COMMENT '执行时间',
    `execute_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '执行管理员ID',
    
    -- 附件凭证
    `attach_images` TEXT COMMENT '附件图片(JSON数组)',
    
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_change_sn` (`change_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_change_type` (`change_type`),
    KEY `idx_change_status` (`change_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更申请表';

-- =============================================
-- 2. 订单转让表
-- 支持订单在用户之间转让
-- =============================================
DROP TABLE IF EXISTS `la_order_transfer`;
CREATE TABLE `la_order_transfer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `transfer_sn` VARCHAR(32) NOT NULL COMMENT '转让单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    
    -- 转让方
    `from_user_id` INT UNSIGNED NOT NULL COMMENT '转让方用户ID',
    `from_user_name` VARCHAR(50) DEFAULT '' COMMENT '转让方姓名',
    `from_user_mobile` VARCHAR(20) DEFAULT '' COMMENT '转让方电话',
    
    -- 接收方
    `to_user_id` INT UNSIGNED DEFAULT 0 COMMENT '接收方用户ID（注册用户）',
    `to_user_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '接收方姓名',
    `to_user_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '接收方电话',
    `to_user_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '接收方是否验证：0=否,1=是',
    
    -- 转让信息
    `transfer_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '转让状态：0=待审核,1=待接收,2=接收确认,3=转让完成,4=已拒绝,5=已取消',
    `transfer_reason` VARCHAR(500) DEFAULT '' COMMENT '转让原因',
    `transfer_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT '转让手续费',
    `fee_paid` TINYINT UNSIGNED DEFAULT 0 COMMENT '手续费是否支付：0=否,1=是',
    
    -- 审核相关
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    -- 接收方确认
    `accept_code` VARCHAR(10) DEFAULT '' COMMENT '接收验证码',
    `accept_code_expire` INT UNSIGNED DEFAULT 0 COMMENT '验证码过期时间',
    `accept_code_send_time` INT UNSIGNED DEFAULT 0 COMMENT '验证码发送时间',
    `accept_code_send_count` TINYINT UNSIGNED DEFAULT 0 COMMENT '验证码发送次数',
    `accept_time` INT UNSIGNED DEFAULT 0 COMMENT '接收时间',
    
    -- 完成相关
    `complete_time` INT UNSIGNED DEFAULT 0 COMMENT '完成时间',
    `complete_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '完成操作管理员ID',
    
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_transfer_sn` (`transfer_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_from_user` (`from_user_id`),
    KEY `idx_to_user` (`to_user_id`),
    KEY `idx_to_mobile` (`to_user_mobile`),
    KEY `idx_transfer_status` (`transfer_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单转让表';

-- =============================================
-- 3. 订单暂停表
-- 支持特殊情况订单暂停和恢复
-- =============================================
DROP TABLE IF EXISTS `la_order_pause`;
CREATE TABLE `la_order_pause` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `pause_sn` VARCHAR(32) NOT NULL COMMENT '暂停单号',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `order_sn` VARCHAR(32) NOT NULL COMMENT '订单编号(冗余)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    
    `pause_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '暂停状态：0=待审核,1=暂停中,2=已恢复,3=已拒绝,4=已取消',
    `pause_type` TINYINT UNSIGNED NOT NULL COMMENT '暂停类型：1=疫情,2=突发事件,3=个人原因,4=其他',
    `pause_reason` VARCHAR(500) NOT NULL COMMENT '暂停原因',
    
    -- 暂停期限
    `pause_start_date` DATE DEFAULT NULL COMMENT '暂停开始日期',
    `pause_end_date` DATE DEFAULT NULL COMMENT '暂停结束日期(预计)',
    `pause_days` INT UNSIGNED DEFAULT 0 COMMENT '暂停天数',
    `original_service_date` DATE DEFAULT NULL COMMENT '原服务日期',
    
    -- 审核相关
    `audit_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT 0 COMMENT '审核时间',
    `audit_remark` VARCHAR(500) DEFAULT '' COMMENT '审核备注',
    `reject_reason` VARCHAR(500) DEFAULT '' COMMENT '拒绝原因',
    
    -- 恢复相关
    `resume_time` INT UNSIGNED DEFAULT 0 COMMENT '恢复时间',
    `resume_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '恢复操作管理员ID',
    `resume_remark` VARCHAR(500) DEFAULT '' COMMENT '恢复备注',
    `actual_pause_days` INT UNSIGNED DEFAULT 0 COMMENT '实际暂停天数',
    `new_service_date` DATE DEFAULT NULL COMMENT '恢复后新服务日期',
    
    -- 提醒设置
    `remind_before_days` INT UNSIGNED DEFAULT 3 COMMENT '提前提醒天数',
    `reminded` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否已提醒：0=否,1=是',
    `remind_time` INT UNSIGNED DEFAULT 0 COMMENT '提醒时间',
    
    -- 附件
    `proof_images` TEXT COMMENT '证明材料图片(JSON数组)',
    
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pause_sn` (`pause_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_pause_status` (`pause_status`),
    KEY `idx_pause_end_date` (`pause_end_date`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单暂停表';

-- =============================================
-- 4. 订单变更日志表
-- 记录所有变更操作的详细日志
-- =============================================
DROP TABLE IF EXISTS `la_order_change_log`;
CREATE TABLE `la_order_change_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `related_type` TINYINT UNSIGNED NOT NULL COMMENT '关联类型：1=变更,2=转让,3=暂停',
    `related_id` INT UNSIGNED NOT NULL COMMENT '关联记录ID',
    `operator_type` TINYINT UNSIGNED NOT NULL COMMENT '操作者类型：1=用户,2=管理员,3=系统',
    `operator_id` INT UNSIGNED DEFAULT 0 COMMENT '操作者ID',
    `operator_name` VARCHAR(50) DEFAULT '' COMMENT '操作者名称',
    `action` VARCHAR(50) NOT NULL COMMENT '操作动作',
    `before_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '操作前状态',
    `after_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '操作后状态',
    `before_data` TEXT COMMENT '变更前数据(JSON)',
    `after_data` TEXT COMMENT '变更后数据(JSON)',
    `content` VARCHAR(500) DEFAULT '' COMMENT '日志内容',
    `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT '用户代理',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_related` (`related_type`, `related_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单变更日志表';

-- =============================================
-- 5. 修改订单表，添加新字段
-- =============================================
ALTER TABLE `la_order` 
ADD COLUMN `is_paused` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否暂停：0=否,1=是' AFTER `is_reviewed`,
ADD COLUMN `pause_id` INT UNSIGNED DEFAULT 0 COMMENT '关联暂停记录ID' AFTER `is_paused`,
ADD COLUMN `has_changed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否有变更记录：0=否,1=是' AFTER `pause_id`,
ADD COLUMN `change_count` INT UNSIGNED DEFAULT 0 COMMENT '变更次数' AFTER `has_changed`,
ADD COLUMN `is_transferred` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否已转让：0=否,1=是' AFTER `change_count`,
ADD COLUMN `transfer_id` INT UNSIGNED DEFAULT 0 COMMENT '关联转让记录ID' AFTER `is_transferred`,
ADD COLUMN `original_user_id` INT UNSIGNED DEFAULT 0 COMMENT '原用户ID(转让前)' AFTER `transfer_id`;

-- 添加索引
ALTER TABLE `la_order`
ADD KEY `idx_is_paused` (`is_paused`),
ADD KEY `idx_is_transferred` (`is_transferred`);

-- =============================================
-- 6. 修改订单项表，添加新字段
-- =============================================
ALTER TABLE `la_order_item`
ADD COLUMN `is_changed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否变更过：0=否,1=是' AFTER `item_status`,
ADD COLUMN `change_id` INT UNSIGNED DEFAULT 0 COMMENT '关联变更记录ID' AFTER `is_changed`,
ADD COLUMN `original_staff_id` INT UNSIGNED DEFAULT 0 COMMENT '原工作人员ID(换人前)' AFTER `change_id`,
ADD COLUMN `original_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '原价格(换人前)' AFTER `original_staff_id`;

-- =============================================
-- 7. 初始化数据（暂停类型字典）
-- =============================================
INSERT INTO `la_dict_data`
(`type_id`, `name`, `value`, `type_value`, `sort`, `status`, `remark`, `create_time`, `update_time`)
VALUES
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '疫情', '1', '1', 1, 1, '订单暂停类型-疫情', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '突发事件', '2', '2', 2, 1, '订单暂停类型-突发事件', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '个人原因', '3', '3', 3, 1, '订单暂停类型-个人原因', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
((SELECT id FROM `la_dict_type` WHERE `type` = 'system_config' LIMIT 1),
 '其他', '4', '4', 4, 1, '订单暂停类型-其他', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
-- =============================================
-- 说明：
-- 1. la_order_change 表支持三种变更类型：改期(1)、换人(2)、加项(3)
-- 2. 差价处理：price_diff 正数表示用户需补付，负数表示退款给用户
-- 3. la_order_transfer 表支持订单在用户间转让，需验证码确认
-- 4. la_order_pause 表支持订单暂停，可设置暂停期限和提醒
-- 5. la_order_change_log 记录所有变更操作的完整日志
-- =============================================
