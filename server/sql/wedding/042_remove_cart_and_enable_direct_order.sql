-- =====================================================
-- 去购物车并切换为单服务直购
-- 创建时间: 2026-03-17
-- 说明:
-- 1. 清空购物车与方案运行态数据
-- 2. 释放未关联订单的套餐临时锁
-- 3. 配合代码发布窗口执行
-- =====================================================

SET @cleanup_time = UNIX_TIMESTAMP();

-- 清空购物车与方案数据
DELETE FROM `la_cart`;
DELETE FROM `la_cart_plan`;

-- 释放所有未成单的套餐临时锁
UPDATE `la_package_booking`
SET
    `status` = 0,
    `time_slot` = 0,
    `lock_expire_time` = NULL,
    `order_id` = NULL,
    `order_item_id` = NULL,
    `update_time` = @cleanup_time
WHERE `order_id` IS NULL
  AND `status` = 1;
