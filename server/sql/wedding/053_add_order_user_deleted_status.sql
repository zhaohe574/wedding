-- ============================================================
-- 053_add_order_user_deleted_status.sql
-- 说明：新增订单业务状态“用户已删除”，前台删除不再写 delete_time
-- 执行日期：2026-03-31
-- ============================================================

ALTER TABLE `la_order`
    MODIFY COLUMN `order_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态：0=待确认,1=待支付,2=待服务,3=服务中,4=已完成,5=已评价,6=已取消,7=已暂停,8=已退款,9=用户已删除';
