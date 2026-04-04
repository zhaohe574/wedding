-- =============================================
-- 菜单角色重绑脚本
-- 文件名：role_menu_rebind.sql
-- 说明：
-- 1) 新一级菜单按“角色已有子菜单”自动补绑
-- 2) 服务人员角色仅绑定「服务人员中心」专属菜单
-- 3) 清理旧归档一级菜单绑定
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @root_workbench_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'workbench' ORDER BY `id` DESC LIMIT 1);
SET @root_ops_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'ops' ORDER BY `id` DESC LIMIT 1);
SET @root_growth_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'growth' ORDER BY `id` DESC LIMIT 1);
SET @root_finance_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'finance-settlement' ORDER BY `id` DESC LIMIT 1);
SET @root_content_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'content' ORDER BY `id` DESC LIMIT 1);
SET @root_experience_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'experience' ORDER BY `id` DESC LIMIT 1);
SET @root_platform_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'platform' ORDER BY `id` DESC LIMIT 1);
SET @root_org_auth_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'org-auth' ORDER BY `id` DESC LIMIT 1);
SET @root_tools_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'system-tools' ORDER BY `id` DESC LIMIT 1);
SET @root_staff_center_id = (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'staff-center' ORDER BY `id` DESC LIMIT 1);

-- ---------------------------------------------
-- 1) 给所有角色自动补绑新一级菜单（依据已拥有子菜单）
-- ---------------------------------------------
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT rm.`role_id`, m.`pid`
FROM `la_system_role_menu` rm
JOIN `la_system_menu` m ON m.`id` = rm.`menu_id`
WHERE m.`type` = 'C'
  AND m.`pid` IN (@root_workbench_id, @root_ops_id, @root_growth_id, @root_finance_id, @root_content_id, @root_experience_id, @root_platform_id, @root_org_auth_id, @root_tools_id, @root_staff_center_id)
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rmx
      WHERE rmx.`role_id` = rm.`role_id` AND rmx.`menu_id` = m.`pid`
  );

-- ---------------------------------------------
-- 2) 服务人员角色专属绑定
-- ---------------------------------------------
SET @staff_role_id = (
    SELECT `id` FROM `la_system_role`
    WHERE `name` = '服务人员' AND `delete_time` IS NULL
    ORDER BY `id` DESC
    LIMIT 1
);

-- 清理服务人员角色上的运营后台权限（仅保留服务人员中心整棵子树）
DELETE rm
FROM `la_system_role_menu` rm
JOIN `la_system_menu` m ON m.`id` = rm.`menu_id`
WHERE rm.`role_id` = @staff_role_id
  AND @staff_role_id IS NOT NULL
  AND NOT (
      m.`id` = @root_staff_center_id
      OR m.`pid` = @root_staff_center_id
      OR m.`pid` IN (SELECT `id` FROM `la_system_menu` WHERE `pid` = @root_staff_center_id AND `type` = 'C')
  );

-- 绑定服务人员中心（M + C + A）
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.`id`
FROM `la_system_menu` m
WHERE @staff_role_id IS NOT NULL
  AND (
      m.`id` = @root_staff_center_id
      OR m.`pid` = @root_staff_center_id
      OR m.`pid` IN (SELECT `id` FROM `la_system_menu` WHERE `pid` = @root_staff_center_id AND `type` = 'C')
  )
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.`role_id` = @staff_role_id AND rm.`menu_id` = m.`id`
  );

-- ---------------------------------------------
-- 3) 清理旧一级菜单绑定（归档目录）
-- ---------------------------------------------
DELETE rm
FROM `la_system_role_menu` rm
JOIN `la_system_menu` m ON m.`id` = rm.`menu_id`
WHERE m.`type` = 'M'
  AND m.`pid` = 0
  AND m.`paths` IN (
      'service','schedule','order','aftersale','review','dynamic','crm','financial','marketing','message','timeline',
      'consumer','article','material','channel','decoration','setting','organization','permission','template','dev_tools','app','finance','staff_center'
  );

SET FOREIGN_KEY_CHECKS = 1;
