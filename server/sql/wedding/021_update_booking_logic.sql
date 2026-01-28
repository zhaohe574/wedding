-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 预约逻辑调整（套餐类型/场次/候补批次/套餐预订锁）
-- | 说明：保持与现有 JSON 文本字段一致，允许空值，由应用层补默认值
-- +----------------------------------------------------------------------

-- 1. 服务套餐新增预约类型与允许场次配置
ALTER TABLE `la_service_package`
ADD COLUMN `booking_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '预约类型：0=全天套餐,1=分场次套餐' AFTER `slot_prices`,
ADD COLUMN `allowed_time_slots` TEXT NULL COMMENT '允许场次(JSON数组)' AFTER `booking_type`;

-- 2. 工作人员套餐新增预约类型与允许场次覆盖配置
ALTER TABLE `la_staff_package`
ADD COLUMN `booking_type` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT '预约类型覆盖：0=全天套餐,1=分场次套餐' AFTER `custom_slot_prices`,
ADD COLUMN `allowed_time_slots` TEXT NULL COMMENT '允许场次覆盖(JSON数组)' AFTER `booking_type`;

-- 3. 套餐预订锁新增 time_slot 维度并更新唯一索引
ALTER TABLE `la_package_booking`
ADD COLUMN `time_slot` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时间段：0=全天,1=早礼,2=午宴,3=晚宴' AFTER `booking_date`;

ALTER TABLE `la_package_booking`
DROP INDEX `uk_package_date_active`,
ADD UNIQUE KEY `uk_staff_package_date_slot` (`staff_id`, `package_id`, `booking_date`, `time_slot`);

-- 4. 候补表新增批次号（整单候补）
ALTER TABLE `la_waitlist`
ADD COLUMN `batch_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '候补批次号' AFTER `package_id`;

-- 5. 兼容既有数据：默认全量可选场次由应用层补齐
UPDATE `la_service_package` SET `booking_type` = 0 WHERE `booking_type` IS NULL;
