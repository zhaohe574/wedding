-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 套餐系统优化迁移脚本
-- | 功能：混合模式套餐绑定、时段差异化定价、单日唯一预订限制
-- +----------------------------------------------------------------------

-- ===================================================================
-- 1. 修改 la_service_package 表 - 添加套餐类型和时段价格支持
-- ===================================================================
ALTER TABLE `la_service_package` 
ADD COLUMN `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属工作人员ID（0=全局套餐）' AFTER `category_id`,
ADD COLUMN `package_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '套餐类型：1=全局套餐,2=人员专属套餐' AFTER `staff_id`,
ADD COLUMN `slot_prices` TEXT COMMENT '时段价格配置JSON：[{"start_time":"08:00","end_time":"12:00","price":1000}]' AFTER `price`,
ADD INDEX `idx_staff_id` (`staff_id`),
ADD INDEX `idx_package_type` (`package_type`);

-- ===================================================================
-- 2. 修改 la_staff_package 表 - 添加个人定制价格和时段价格
-- ===================================================================
ALTER TABLE `la_staff_package` 
ADD COLUMN `custom_price` DECIMAL(10,2) DEFAULT NULL COMMENT '个人定制价格（覆盖套餐默认价格）' AFTER `package_id`,
ADD COLUMN `custom_slot_prices` TEXT COMMENT '个人时段价格配置JSON' AFTER `custom_price`,
ADD COLUMN `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用' AFTER `custom_slot_prices`;

-- ===================================================================
-- 3. 新建 la_package_booking 表 - 套餐预订记录（单日唯一限制）
-- ===================================================================
CREATE TABLE IF NOT EXISTS `la_package_booking` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
  `package_id` INT UNSIGNED NOT NULL COMMENT '套餐ID',
  `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID（0=不限人员）',
  `booking_date` DATE NOT NULL COMMENT '预订日期',
  `start_time` VARCHAR(10) DEFAULT NULL COMMENT '开始时间 HH:mm',
  `end_time` VARCHAR(10) DEFAULT NULL COMMENT '结束时间 HH:mm',
  `order_id` INT UNSIGNED DEFAULT NULL COMMENT '关联订单ID',
  `order_item_id` INT UNSIGNED DEFAULT NULL COMMENT '关联订单项ID',
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT '预订用户ID',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=已释放,1=临时锁定,2=已确认',
  `lock_expire_time` INT UNSIGNED DEFAULT NULL COMMENT '临时锁定过期时间戳',
  `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '乐观锁版本号',
  `create_time` INT UNSIGNED NOT NULL COMMENT '创建时间',
  `update_time` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
  UNIQUE KEY `uk_package_date_active` (`package_id`, `booking_date`, `status`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_lock_expire` (`lock_expire_time`),
  INDEX `idx_booking_date` (`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='套餐预订记录表（用于单日唯一限制）';

-- ===================================================================
-- 4. 更新现有数据 - 将现有套餐标记为全局套餐
-- ===================================================================
UPDATE `la_service_package` SET `package_type` = 1, `staff_id` = 0 WHERE `package_type` IS NULL OR `package_type` = 0;

-- ===================================================================
-- 5. 添加清理过期锁定的事件（可选，需要MySQL开启事件调度器）
-- ===================================================================
-- SET GLOBAL event_scheduler = ON;

-- DROP EVENT IF EXISTS `clear_expired_package_locks`;
-- CREATE EVENT `clear_expired_package_locks`
-- ON SCHEDULE EVERY 5 MINUTE
-- DO
--   UPDATE `la_package_booking` 
--   SET `status` = 0 
--   WHERE `status` = 1 
--   AND `lock_expire_time` IS NOT NULL 
--   AND `lock_expire_time` < UNIX_TIMESTAMP();
