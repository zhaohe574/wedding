-- =============================================
-- 订单项增加确认状态字段，用于多服务人员分项确认
-- =============================================

ALTER TABLE `la_order_item`
    ADD COLUMN `confirm_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '确认状态：0=待确认,1=已确认' AFTER `item_status`;

-- 历史数据：待确认订单置为未确认，其余视为已确认
UPDATE `la_order_item` oi
JOIN `la_order` o ON o.`id` = oi.`order_id`
SET oi.`confirm_status` = IF(o.`order_status` = 0, 0, 1);
