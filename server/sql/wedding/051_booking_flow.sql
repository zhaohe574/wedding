-- =============================================
-- 新预约流程字段升级
-- 说明：
-- 1. 下线旧 addon 预约链路后，新增服务人员自定义附加项配置
-- 2. 新增分类级别的管家/督导关联配置
-- 3. 订单项支持主服务 / 自定义附加项 / 关联服务人员三类
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `la_staff`
    ADD COLUMN IF NOT EXISTS `booking_option_1_name` varchar(100) NOT NULL DEFAULT '' COMMENT '预约附加项1名称' AFTER `service_desc`,
    ADD COLUMN IF NOT EXISTS `booking_option_1_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '预约附加项1价格' AFTER `booking_option_1_name`,
    ADD COLUMN IF NOT EXISTS `booking_option_2_name` varchar(100) NOT NULL DEFAULT '' COMMENT '预约附加项2名称' AFTER `booking_option_1_price`,
    ADD COLUMN IF NOT EXISTS `booking_option_2_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '预约附加项2价格' AFTER `booking_option_2_name`;

ALTER TABLE `la_service_category`
    ADD COLUMN IF NOT EXISTS `booking_butler_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否启用婚礼管家预约：0-否，1-是' AFTER `image`,
    ADD COLUMN IF NOT EXISTS `booking_butler_category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '婚礼管家关联服务分类ID' AFTER `booking_butler_enabled`,
    ADD COLUMN IF NOT EXISTS `booking_director_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否启用婚礼督导预约：0-否，1-是' AFTER `booking_butler_category_id`,
    ADD COLUMN IF NOT EXISTS `booking_director_category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '婚礼督导关联服务分类ID' AFTER `booking_director_enabled`;

ALTER TABLE `la_order_item`
    ADD COLUMN IF NOT EXISTS `item_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单项类型：1=主服务，2=预约附加项，3=关联服务人员' AFTER `subtotal`,
    ADD COLUMN IF NOT EXISTS `item_meta` text NULL COMMENT '订单项扩展信息(JSON)' AFTER `item_type`;

SET FOREIGN_KEY_CHECKS = 1;
