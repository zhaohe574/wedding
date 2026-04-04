-- =============================================
-- 旧版附加服务菜单下线
-- 说明：
-- 1. 用于已存在的本地测试库执行下线校正
-- 2. 与 server/sql/2.0.0.20260201/update.sql 保持一致
-- 3. 不删除历史菜单记录，只做隐藏、禁用与角色解绑
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

UPDATE `la_system_menu`
SET `is_show` = 0,
    `is_disable` = 1,
    `update_time` = UNIX_TIMESTAMP()
WHERE `perms` LIKE 'ops.addon/%';

DELETE rm
FROM `la_system_role_menu` rm
INNER JOIN `la_system_menu` sm ON sm.`id` = rm.`menu_id`
WHERE sm.`perms` LIKE 'ops.addon/%';

SET FOREIGN_KEY_CHECKS = 1;
