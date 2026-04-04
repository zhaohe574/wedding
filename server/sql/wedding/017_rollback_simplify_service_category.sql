-- +----------------------------------------------------------------------
-- | 婚庆服务预约系统 - 服务分类表结构简化回滚脚本
-- +----------------------------------------------------------------------
-- | 说明：恢复 pid 和 level 字段，用于回滚到多级分类结构
-- | 创建时间：2026-01-23
-- +----------------------------------------------------------------------

-- 1. 恢复 pid 字段
ALTER TABLE `la_service_category` 
ADD COLUMN `pid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级分类ID' AFTER `name`;

-- 2. 恢复 level 字段
ALTER TABLE `la_service_category` 
ADD COLUMN `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类层级:1-一级,2-二级,3-三级' AFTER `pid`;

-- 3. 恢复索引
ALTER TABLE `la_service_category` ADD KEY `idx_pid` (`pid`) USING BTREE;
ALTER TABLE `la_service_category` ADD KEY `idx_level` (`level`) USING BTREE;

-- 4. 恢复数据（所有分类设置为一级分类）
UPDATE `la_service_category` SET `pid` = 0, `level` = 1 WHERE `delete_time` IS NULL;
