-- =============================================
-- 婚庆服务预约系统 - 数据库更新
-- 添加服务分类层级字段
-- 创建日期: 2026-01-23
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 为 la_service_category 表添加 level 字段
-- ----------------------------
ALTER TABLE `la_service_category` 
ADD COLUMN `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类层级:1-一级,2-二级,3-三级' AFTER `pid`;

-- ----------------------------
-- 更新现有数据的 level 值
-- 所有现有分类都是一级分类（pid=0）
-- ----------------------------
UPDATE `la_service_category` SET `level` = 1 WHERE `pid` = 0;

-- ----------------------------
-- 添加索引
-- ----------------------------
ALTER TABLE `la_service_category` 
ADD KEY `idx_level` (`level`) USING BTREE;

SET FOREIGN_KEY_CHECKS = 1;
