-- =============================================
-- 服务人员：新增「添加/编辑」菜单，修复「新增人员」按钮无法跳转
-- 原因：getRoutePath('staff.staff/add:edit') 依赖该菜单的 perms，缺省时返回空导致 router-link 无跳转
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 在「服务管理」下新增「服务人员添加/编辑」页面（不在侧栏展示，供新增/编辑人员跳转）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '服务人员添加/编辑', '', 99, 'staff.staff/add:edit', 'staff/edit', 'staff/lists/edit', '/service/staff', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'staff.staff/add:edit')
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
