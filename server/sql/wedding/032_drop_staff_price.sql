-- =============================================
-- 删除服务人员主表价格字段（改为套餐自动价）
-- 创建日期: 2026-03-04
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) 删除索引
ALTER TABLE `la_staff`
    DROP INDEX `idx_price`;

-- 2) 删除字段
ALTER TABLE `la_staff`
    DROP COLUMN `price`;

SET FOREIGN_KEY_CHECKS = 1;
