-- ============================================================
-- 059_add_order_refund_workflow.sql
-- 预约订单退款链路升级
-- 1. 增加订单退款中状态说明
-- 2. 为退款记录补充来源状态和实际退款金额
-- 3. 新增退款子项表，支持余额/微信/支付宝/线下拆分退款
-- ============================================================

-- 订单状态补充：10 = 退款中
ALTER TABLE `la_order`
    MODIFY COLUMN `order_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态：0=待确认,1=待支付,2=待服务,3=服务中,4=已完成,5=已评价,6=已取消,7=已暂停,8=已退款,9=用户已删除,10=退款中';

-- 退款记录补充字段
ALTER TABLE `la_refund`
    ADD COLUMN `actual_refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际退款金额' AFTER `refund_amount`,
    ADD COLUMN `source_order_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发起退款前订单状态' AFTER `refund_status`,
    ADD COLUMN `source_pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发起退款前支付状态' AFTER `source_order_status`,
    MODIFY COLUMN `refund_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0=待审核,1=审核通过,2=退款中,3=已退款,4=已拒绝,5=退款失败';

-- 退款子项表
CREATE TABLE `la_refund_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `refund_id` INT UNSIGNED NOT NULL COMMENT '退款单ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `payment_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付流水ID',
    `pay_way` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式：1=微信,2=支付宝,3=余额,4=线下',
    `pay_terminal` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付终端',
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '子项退款金额',
    `refund_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0=待执行,1=处理中,2=已完成,3=失败',
    `out_refund_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '商户退款单号',
    `third_refund_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方退款单号',
    `refund_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '退款处理说明',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款完成时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_out_refund_no` (`out_refund_no`),
    KEY `idx_refund_id` (`refund_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_payment_id` (`payment_id`),
    KEY `idx_refund_status` (`refund_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='退款子项表';
