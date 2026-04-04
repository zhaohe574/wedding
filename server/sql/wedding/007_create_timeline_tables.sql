-- ============================================================
-- 婚庆服务预约系统 - 婚礼时间轴数据表
-- 创建日期: 2026-01-20
-- ============================================================

-- -----------------------------------------------------------
-- 1. 时间轴模板表 (la_timeline_template)
-- 预设的婚礼筹备任务模板
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_timeline_template`;
CREATE TABLE `la_timeline_template` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_name` VARCHAR(100) NOT NULL COMMENT '模板名称',
    `template_desc` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '模板描述',
    `service_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '适用服务类型：0=通用,1=摄影,2=化妆,3=全套服务',
    `tasks` TEXT NOT NULL COMMENT '任务配置JSON [{title, desc, days_before, type}]',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认模板：0=否,1=是',
    `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用：0=禁用,1=启用',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重',
    `use_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_service_type` (`service_type`),
    KEY `idx_is_default` (`is_default`),
    KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='时间轴模板表';

-- -----------------------------------------------------------
-- 2. 订单时间轴任务表 (la_order_timeline)
-- 每个订单的具体时间轴任务
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `la_order_timeline`;
CREATE TABLE `la_order_timeline` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '来源模板ID',
    `wedding_date` DATE NOT NULL COMMENT '婚期日期',
    `task_title` VARCHAR(100) NOT NULL COMMENT '任务标题',
    `task_desc` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '任务描述',
    `task_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '任务类型：1=准备物料,2=确认事项,3=沟通联系,4=现场安排,5=其他',
    `days_before` INT NOT NULL DEFAULT 0 COMMENT '婚期前N天(0=当天,负数=婚期后)',
    `trigger_date` DATE NOT NULL COMMENT '实际触发日期',
    `trigger_time` TIME DEFAULT NULL COMMENT '触发时间(可选)',
    `is_completed` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否完成：0=未完成,1=已完成',
    `complete_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
    `complete_user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成人ID',
    `complete_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '完成备注',
    `is_reminded` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已提醒：0=未提醒,1=已提醒',
    `remind_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '提醒时间',
    `is_system` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否系统任务：0=手动添加,1=系统生成',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序权重',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_wedding_date` (`wedding_date`),
    KEY `idx_trigger_date` (`trigger_date`),
    KEY `idx_is_completed` (`is_completed`),
    KEY `idx_task_type` (`task_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='订单时间轴任务表';

-- -----------------------------------------------------------
-- 3. 初始化默认时间轴模板数据
-- -----------------------------------------------------------
INSERT INTO `la_timeline_template` (`template_name`, `template_desc`, `service_type`, `tasks`, `is_default`, `is_enabled`, `sort`, `create_time`, `update_time`) VALUES
('通用婚礼筹备模板', '适用于所有类型婚礼服务的标准筹备流程', 0, '[{"title":"确认服务细节","desc":"与工作人员确认服务内容、时间、地点等细节","days_before":30,"type":2},{"title":"试妆预约","desc":"预约试妆时间，确认妆容造型","days_before":21,"type":2},{"title":"最终方案确认","desc":"确认最终服务方案，签署补充协议","days_before":14,"type":2},{"title":"物料清单核对","desc":"核对婚礼当天所需物料清单","days_before":7,"type":1},{"title":"与工作人员确认集合时间","desc":"确认婚礼当天集合时间、地点和联系方式","days_before":3,"type":3},{"title":"婚礼前一天确认","desc":"最终确认所有细节，确保万无一失","days_before":1,"type":2},{"title":"婚礼当天","desc":"婚礼当天服务开始","days_before":0,"type":4}]', 1, 1, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('摄影服务专属模板', '适用于婚礼摄影、婚纱照等摄影服务', 1, '[{"title":"确认拍摄风格","desc":"与摄影师沟通确认拍摄风格和场景","days_before":30,"type":2},{"title":"场地踩点","desc":"提前踩点拍摄场地，规划拍摄路线","days_before":14,"type":2},{"title":"确认拍摄服装","desc":"确认拍摄当天的服装和配饰","days_before":7,"type":1},{"title":"设备检查","desc":"摄影师检查设备，确保万无一失","days_before":3,"type":1},{"title":"拍摄当天","desc":"拍摄服务开始","days_before":0,"type":4},{"title":"初片交付","desc":"交付初选照片供客户挑选","days_before":-7,"type":5},{"title":"精修交付","desc":"交付精修照片","days_before":-21,"type":5}]', 0, 1, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('化妆服务专属模板', '适用于新娘化妆、跟妆等化妆服务', 2, '[{"title":"确认妆容风格","desc":"与化妆师沟通确认妆容风格","days_before":30,"type":2},{"title":"试妆","desc":"进行试妆，调整妆容细节","days_before":14,"type":2},{"title":"确认化妆品","desc":"确认婚礼当天使用的化妆品","days_before":7,"type":1},{"title":"皮肤护理提醒","desc":"婚前皮肤护理，保持最佳状态","days_before":3,"type":2},{"title":"化妆当天","desc":"婚礼当天化妆服务开始","days_before":0,"type":4}]', 0, 1, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
