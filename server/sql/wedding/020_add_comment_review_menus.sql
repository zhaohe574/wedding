-- =============================================
-- 动态评论审核功能 - 菜单配置
-- 创建日期: 2026-01-24
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 获取动态社区菜单ID
-- ----------------------------
SET @dynamic_menu_id = (SELECT id FROM `la_system_menu` WHERE `name` = '动态社区' AND `pid` = 0 LIMIT 1);

-- ----------------------------
-- 添加评论审核菜单
-- ----------------------------
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '评论审核', '', 90, 'dynamic.dynamicComment/reviewList', 'comment/review', 'dynamic/comment/review', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 添加动态配置菜单
-- ----------------------------
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '动态配置', '', 80, 'dynamic.dynamicComment/getReviewConfig', 'config', 'dynamic/config/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;
