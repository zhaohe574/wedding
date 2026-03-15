-- =============================================
-- 菜单与角色回滚脚本
-- 文件名：rollback_snapshot.sql
-- 依赖快照表：
-- - la_system_menu_snapshot_admin_refactor_20260304
-- - la_system_role_menu_snapshot_admin_refactor_20260304
-- - la_system_role_snapshot_admin_refactor_20260304
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 先删关联表，避免外键冲突
DELETE FROM `la_system_role_menu`;
DELETE FROM `la_system_menu`;
DELETE FROM `la_system_role`;

-- 还原角色
INSERT INTO `la_system_role`
SELECT * FROM `la_system_role_snapshot_admin_refactor_20260304`;

-- 还原菜单
INSERT INTO `la_system_menu`
SELECT * FROM `la_system_menu_snapshot_admin_refactor_20260304`;

-- 还原角色菜单关系
INSERT INTO `la_system_role_menu`
SELECT * FROM `la_system_role_menu_snapshot_admin_refactor_20260304`;

SET FOREIGN_KEY_CHECKS = 1;
