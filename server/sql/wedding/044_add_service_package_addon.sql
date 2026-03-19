-- =====================================================
-- 套餐附加服务关联表
-- 用途：
-- 1. 支持主套餐配置可选附加服务
-- 2. 前台下单和订单附加服务变更按套餐过滤附加服务
-- =====================================================

CREATE TABLE IF NOT EXISTS `la_service_package_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_package_addon` (`package_id`, `addon_id`),
    KEY `idx_package_id` (`package_id`),
    KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套餐附加服务关联表';
