-- =====================================================
-- 微信小程序订阅消息系统数据库表
-- 创建时间: 2026-01-20
-- 说明: 支持微信小程序服务消息推送功能
-- =====================================================

-- -----------------------------------------------------
-- 订阅消息模板表
-- 存储微信小程序订阅消息模板配置
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `la_subscribe_message_template` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '模板名称',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '模板标题',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景: order_create/order_paid/order_confirm/order_complete/schedule_remind/refund_result/callback_remind',
    `content` TEXT COMMENT '模板内容(JSON格式，存储字段配置)',
    `example` TEXT COMMENT '示例内容',
    `keywords` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '关键词列表(逗号分隔)',
    `category_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '模板类目ID',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0-禁用 1-启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序(越大越靠前)',
    `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '备注说明',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_id` (`template_id`),
    KEY `idx_scene` (`scene`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息模板表';

-- -----------------------------------------------------
-- 用户订阅记录表
-- 记录用户对各模板的订阅授权情况
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `la_user_subscribe` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `accept_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计授权次数(一次性订阅消息每次授权+1)',
    `reject_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计拒绝次数',
    `last_accept_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后授权时间',
    `last_reject_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后拒绝时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '订阅状态: 0-已拒绝 1-已订阅 2-永久订阅',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_template` (`user_id`, `template_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_scene` (`scene`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户订阅记录表';

-- -----------------------------------------------------
-- 订阅消息发送日志表
-- 记录每次消息发送的详细信息
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `la_subscribe_message_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `openid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '用户OpenID',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信模板ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '使用场景',
    `business_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '业务类型: order/schedule/refund/callback/ticket等',
    `business_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务ID',
    `content` TEXT COMMENT '发送内容(JSON格式)',
    `page` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '跳转页面路径',
    `miniprogram_state` VARCHAR(20) NOT NULL DEFAULT 'formal' COMMENT '小程序状态: developer/trial/formal',
    `send_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送状态: 0-待发送 1-发送成功 2-发送失败',
    `error_code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '错误码',
    `error_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '错误信息',
    `request_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '请求ID',
    `send_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_scene` (`scene`),
    KEY `idx_business` (`business_type`, `business_id`),
    KEY `idx_send_status` (`send_status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息发送日志表';

-- -----------------------------------------------------
-- 订阅消息场景配置表
-- 配置各业务场景的消息触发规则
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `la_subscribe_message_scene` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `scene` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '场景标识',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '场景名称',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '场景描述',
    `template_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '关联模板ID',
    `trigger_event` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '触发事件',
    `data_mapping` TEXT COMMENT '数据映射配置(JSON格式)',
    `page_path` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '默认跳转页面',
    `is_auto` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否自动发送: 0-手动 1-自动',
    `delay_seconds` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '延迟发送秒数',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0-禁用 1-启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_scene` (`scene`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅消息场景配置表';

-- -----------------------------------------------------
-- 初始化订阅消息场景配置
-- -----------------------------------------------------
INSERT INTO `la_subscribe_message_scene` (`scene`, `name`, `description`, `trigger_event`, `page_path`, `is_auto`, `status`, `create_time`, `update_time`) VALUES
('order_create', '订单创建通知', '用户提交订单后发送确认通知', 'OrderCreated', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_paid', '支付成功通知', '用户完成支付后发送确认通知', 'OrderPaid', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_confirm', '订单确认通知', '商家确认订单后通知用户', 'OrderConfirmed', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('order_complete', '服务完成通知', '服务完成后通知用户进行评价', 'OrderCompleted', 'pages/review/publish', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('schedule_remind', '档期提醒通知', '服务日期前提醒用户', 'ScheduleRemind', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('refund_result', '退款结果通知', '退款审核结果通知', 'RefundProcessed', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('callback_remind', '回访提醒通知', '服务完成后的回访提醒', 'CallbackRemind', 'pages/aftersale/callback', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ticket_update', '工单进度通知', '售后工单状态更新通知', 'TicketUpdated', 'pages/aftersale/ticket_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('change_result', '变更审核通知', '订单变更申请审核结果通知', 'ChangeProcessed', 'pages/order_change/change_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('schedule_change', '档期变更通知', '人员档期发生变更时通知', 'ScheduleChanged', 'pages/order_detail/order_detail', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- -----------------------------------------------------
-- 初始化示例模板配置
-- 注意: template_id需要在微信后台申请后填入
-- -----------------------------------------------------
INSERT INTO `la_subscribe_message_template` (`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`) VALUES
('TEMPLATE_ID_ORDER_CREATE', '订单提交成功通知', '订单提交成功', 'order_create', '{"thing1":{"key":"订单内容","value":""},"character_string2":{"key":"订单编号","value":""},"amount3":{"key":"订单金额","value":""},"time4":{"key":"下单时间","value":""}}', '订单内容,订单编号,订单金额,下单时间', 1, 100, '订单创建后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_ORDER_PAID', '支付成功通知', '支付成功', 'order_paid', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"支付金额","value":""},"time3":{"key":"支付时间","value":""},"thing4":{"key":"商品名称","value":""}}', '订单编号,支付金额,支付时间,商品名称', 1, 99, '支付完成后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_SERVICE_REMIND', '服务提醒通知', '服务提醒', 'schedule_remind', '{"thing1":{"key":"服务内容","value":""},"time2":{"key":"服务时间","value":""},"thing3":{"key":"服务地点","value":""},"thing4":{"key":"服务人员","value":""}}', '服务内容,服务时间,服务地点,服务人员', 1, 98, '服务前1天/3天提醒，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_REFUND_RESULT', '退款结果通知', '退款通知', 'refund_result', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"退款金额","value":""},"phrase3":{"key":"退款状态","value":""},"thing4":{"key":"退款原因","value":""}}', '订单编号,退款金额,退款状态,退款原因', 1, 97, '退款审核后发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TEMPLATE_ID_TICKET_UPDATE', '工单进度通知', '工单状态更新', 'ticket_update', '{"character_string1":{"key":"工单编号","value":""},"phrase2":{"key":"工单状态","value":""},"thing3":{"key":"处理说明","value":""},"time4":{"key":"更新时间","value":""}}', '工单编号,工单状态,处理说明,更新时间', 1, 96, '工单状态变更时发送，需在微信后台申请模板后更新template_id', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
