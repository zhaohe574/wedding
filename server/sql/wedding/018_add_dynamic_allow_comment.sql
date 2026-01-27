-- 添加动态评论开关字段
-- 日期: 2025-01-24
-- 描述: 为 la_dynamic 表添加 allow_comment 字段，用于控制是否允许用户评论

-- 添加 allow_comment 字段
ALTER TABLE `la_dynamic` 
ADD COLUMN `allow_comment` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 
COMMENT '是否允许评论：0=禁止，1=允许' 
AFTER `tags`;

-- 为已存在的记录设置默认值（确保向后兼容）
UPDATE `la_dynamic` SET `allow_comment` = 1 WHERE `allow_comment` = 0 OR `allow_comment` IS NULL;
