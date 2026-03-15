-- =============================================
-- 性能优化：添加复合索引
-- 版本：v2.0.1
-- 说明：为高频查询表添加复合索引，提升查询性能
-- =============================================

-- 档期表：添加复合索引 (staff_id, service_date, status)
-- 用于查询工作人员在特定日期的档期状态
ALTER TABLE `la_schedule`
ADD INDEX `idx_staff_date_status` (`staff_id`, `service_date`, `status`);

-- 套餐预订表：添加复合索引 (package_id, service_date, status)
-- 用于查询套餐在特定日期的预订状态
ALTER TABLE `la_package_booking`
ADD INDEX `idx_package_date_status` (`package_id`, `service_date`, `status`);

-- 套餐预订表：添加复合索引 (staff_id, service_date, status)
-- 用于查询工作人员在特定日期的套餐预订状态
ALTER TABLE `la_package_booking`
ADD INDEX `idx_staff_date_status` (`staff_id`, `service_date`, `status`);

-- 订单项表：添加复合索引 (order_id, staff_id)
-- 用于查询订单的工作人员分配情况
ALTER TABLE `la_order_item`
ADD INDEX `idx_order_staff` (`order_id`, `staff_id`);

-- 订单变更表：添加复合索引 (order_id, change_status)
-- 用于查询订单的变更记录
ALTER TABLE `la_order_change`
ADD INDEX `idx_order_status` (`order_id`, `change_status`);

-- 购物车表：添加复合索引 (user_id, staff_id, schedule_date)
-- 用于查询用户的购物车项和档期冲突检测
ALTER TABLE `la_cart`
ADD INDEX `idx_user_staff_date` (`user_id`, `staff_id`, `schedule_date`);
