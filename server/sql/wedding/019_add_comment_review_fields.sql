-- 添加动态评论审核字段
-- 日期: 2025-01-24
-- 描述: 为 la_dynamic_comment 表添加审核相关字段，支持评论审核功能

-- 添加 review_status 字段（审核状态）
ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核状态：0=待审核，1=已通过，2=已拒绝' 
AFTER `status`;

-- 添加 review_admin_id 字段（审核管理员ID）
ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核管理员ID' 
AFTER `review_status`;

-- 添加 review_time 字段（审核时间）
ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核时间' 
AFTER `review_admin_id`;

-- 添加 review_remark 字段（审核备注/拒绝原因）
ALTER TABLE `la_dynamic_comment` 
ADD COLUMN `review_remark` VARCHAR(255) NOT NULL DEFAULT '' 
COMMENT '审核备注（拒绝原因）' 
AFTER `review_time`;

-- 为已存在的评论记录设置 review_status = 1（已通过），保持向后兼容
UPDATE `la_dynamic_comment` SET `review_status` = 1 WHERE `review_status` = 0;
