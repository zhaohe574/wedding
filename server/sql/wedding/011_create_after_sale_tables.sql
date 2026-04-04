-- =============================================
-- 售后服务系统数据库表
-- 创建时间: 2026-01-20
-- =============================================

-- ----------------------------
-- 售后工单表
-- ----------------------------
DROP TABLE IF EXISTS `la_after_sale_ticket`;
CREATE TABLE `la_after_sale_ticket` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '工单ID',
    `ticket_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '工单编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '工单类型 1投诉 2咨询 3售后 4建议 5其他',
    `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '优先级 1低 2中 3高 4紧急',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '工单标题',
    `content` text COMMENT '工单内容',
    `images` text COMMENT '图片凭证 JSON数组',
    `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
    `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待分配 1处理中 2待确认 3已完成 4已关闭 5已取消',
    `assign_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `assign_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '分配时间',
    `handle_result` text COMMENT '处理结果',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `close_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '关闭原因',
    `close_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关闭时间',
    `satisfaction` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评价 1-5星',
    `satisfaction_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '满意度评价内容',
    `expect_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '期望处理时间',
    `deadline` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理截止时间',
    `is_overtime` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超时 0否 1是',
    `escalate_level` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级次数',
    `escalate_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后升级时间',
    `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源 1小程序 2后台 3电话',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ticket_sn` (`ticket_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_assign_admin_id` (`assign_admin_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后工单表';

-- ----------------------------
-- 工单处理记录表
-- ----------------------------
DROP TABLE IF EXISTS `la_after_sale_ticket_log`;
CREATE TABLE `la_after_sale_ticket_log` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单ID',
    `operator_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '操作人类型 1用户 2管理员 3系统',
    `operator_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人ID',
    `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作动作',
    `old_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作前状态',
    `new_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作后状态',
    `content` text COMMENT '操作内容/备注',
    `images` text COMMENT '附件图片 JSON数组',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_ticket_id` (`ticket_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工单处理记录表';

-- ----------------------------
-- 投诉表
-- ----------------------------
DROP TABLE IF EXISTS `la_complaint`;
CREATE TABLE `la_complaint` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '投诉ID',
    `complaint_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '投诉编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被投诉服务人员ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投诉类型 1服务态度 2专业能力 3迟到早退 4违规行为 5其他',
    `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投诉等级 1一般 2严重 3紧急',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '投诉标题',
    `content` text COMMENT '投诉内容',
    `images` text COMMENT '图片凭证 JSON数组',
    `videos` text COMMENT '视频凭证 JSON数组',
    `expect_result` varchar(500) NOT NULL DEFAULT '' COMMENT '期望处理结果',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待处理 1处理中 2已处理 3已申诉 4已关闭',
    `handle_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理人ID',
    `handle_result` text COMMENT '处理结果',
    `handle_action` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理动作 0无 1警告 2扣款 3禁用 4其他',
    `handle_amount` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '处理涉及金额（扣款/赔偿）',
    `handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `deadline` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理截止时间',
    `is_overtime` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否超时 0否 1是',
    `satisfaction` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评价 1-5星',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_complaint_sn` (`complaint_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_type` (`type`),
    KEY `idx_level` (`level`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='投诉表';

-- ----------------------------
-- 补拍/重拍申请表
-- ----------------------------
DROP TABLE IF EXISTS `la_reshoot`;
CREATE TABLE `la_reshoot` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '申请ID',
    `reshoot_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '申请编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `order_item_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单项ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '原服务人员ID',
    `new_staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新服务人员ID（换人时）',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '申请类型 1补拍 2重拍',
    `reason_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '原因类型 1效果不满意 2天气原因 3设备故障 4人员原因 5其他',
    `reason` text COMMENT '详细原因说明',
    `images` text COMMENT '原片凭证图片 JSON数组',
    `expect_date` date DEFAULT NULL COMMENT '期望拍摄日期',
    `expect_time_slot` varchar(50) NOT NULL DEFAULT '' COMMENT '期望时间段',
    `schedule_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '安排的档期ID',
    `schedule_date` date DEFAULT NULL COMMENT '安排的拍摄日期',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待审核 1审核通过 2审核拒绝 3已安排 4已完成 5已取消',
    `audit_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `audit_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `audit_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '审核备注',
    `is_free` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否免费 0否 1是',
    `fee` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '费用（非免费时）',
    `fee_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '费用状态 0待支付 1已支付',
    `complete_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `complete_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '完成备注',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reshoot_sn` (`reshoot_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='补拍重拍申请表';

-- ----------------------------
-- 服务回访表
-- ----------------------------
DROP TABLE IF EXISTS `la_service_callback`;
CREATE TABLE `la_service_callback` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回访ID',
    `callback_sn` varchar(32) NOT NULL DEFAULT '' COMMENT '回访编号',
    `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `method` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访方式 1电话 2短信 3微信 4小程序问卷',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0待回访 1已回访 2无法联系 3已取消',
    `plan_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '计划回访时间',
    `actual_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '实际回访时间',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访人ID',
    `duration` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访时长（秒）',
    `score` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '满意度评分 0未评 1-5星',
    `score_service` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务态度评分',
    `score_professional` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '专业水平评分',
    `score_punctual` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间守约评分',
    `score_overall` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '整体满意度评分',
    `content` text COMMENT '回访内容/用户反馈',
    `summary` text COMMENT '回访摘要',
    `has_problem` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否有问题 0否 1是',
    `problem_type` varchar(100) NOT NULL DEFAULT '' COMMENT '问题类型',
    `problem_desc` text COMMENT '问题描述',
    `problem_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问题状态 0未处理 1已处理 2已升级',
    `problem_handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问题处理时间',
    `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联工单ID（升级时创建）',
    `retry_count` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
    `next_retry_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次重试时间',
    `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_callback_sn` (`callback_sn`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_plan_time` (`plan_time`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务回访表';

-- ----------------------------
-- 回访问卷配置表
-- ----------------------------
DROP TABLE IF EXISTS `la_callback_questionnaire`;
CREATE TABLE `la_callback_questionnaire` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '问卷标题',
    `description` varchar(500) NOT NULL DEFAULT '' COMMENT '问卷描述',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '回访类型 1服务前 2服务中 3服务后',
    `questions` text COMMENT '问题列表 JSON数组',
    `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` int(11) UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回访问卷配置表';

-- ----------------------------
-- 回访问卷答案表
-- ----------------------------
DROP TABLE IF EXISTS `la_callback_answer`;
CREATE TABLE `la_callback_answer` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `callback_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访ID',
    `questionnaire_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '问卷ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `answers` text COMMENT '答案 JSON数组',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_callback_id` (`callback_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='回访问卷答案表';

-- ----------------------------
-- 问题升级规则配置表
-- ----------------------------
DROP TABLE IF EXISTS `la_escalate_rule`;
CREATE TABLE `la_escalate_rule` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
    `ticket_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单类型 0全部',
    `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '优先级 0全部',
    `timeout_hours` int(11) UNSIGNED NOT NULL DEFAULT 24 COMMENT '超时时间（小时）',
    `escalate_to` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '升级到人员ID',
    `notify_method` varchar(50) NOT NULL DEFAULT 'system' COMMENT '通知方式 system/sms/wechat',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题升级规则配置表';

-- ----------------------------
-- 售后工单统计表（每日汇总）
-- ----------------------------
DROP TABLE IF EXISTS `la_after_sale_daily_stats`;
CREATE TABLE `la_after_sale_daily_stats` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `stat_date` date NOT NULL COMMENT '统计日期',
    `ticket_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单总数',
    `ticket_new` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '新增工单数',
    `ticket_completed` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成工单数',
    `ticket_overtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '超时工单数',
    `complaint_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '投诉总数',
    `complaint_handled` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已处理投诉数',
    `reshoot_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '补拍申请总数',
    `reshoot_approved` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已通过补拍数',
    `callback_total` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回访总数',
    `callback_completed` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已完成回访数',
    `avg_handle_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '平均处理时长（秒）',
    `satisfaction_avg` decimal(3,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '平均满意度',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='售后工单每日统计表';

-- ----------------------------
-- 初始化升级规则
-- ----------------------------
INSERT INTO `la_escalate_rule` (`name`, `ticket_type`, `priority`, `timeout_hours`, `notify_method`, `status`, `create_time`, `update_time`) VALUES
('紧急工单2小时升级', 0, 4, 2, 'system,sms', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('高优先级工单4小时升级', 0, 3, 4, 'system', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('普通工单24小时升级', 0, 2, 24, 'system', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('投诉48小时升级', 1, 0, 48, 'system,sms', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 初始化回访问卷
-- ----------------------------
INSERT INTO `la_callback_questionnaire` (`title`, `description`, `type`, `questions`, `sort`, `status`, `create_time`, `update_time`) VALUES
('服务后满意度调查', '感谢您使用我们的服务，请花1分钟完成以下问卷', 3, '[{"id":1,"type":"rating","title":"整体服务满意度","required":true},{"id":2,"type":"rating","title":"服务人员态度"},{"id":3,"type":"rating","title":"专业水平"},{"id":4,"type":"rating","title":"时间守约"},{"id":5,"type":"text","title":"您的建议","placeholder":"请输入您的宝贵意见..."}]', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
