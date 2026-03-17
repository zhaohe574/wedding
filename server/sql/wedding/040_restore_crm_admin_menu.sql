-- =============================================
-- 恢复 CRM 后台菜单与管理员角色授权
-- 创建日期: 2026-03-16
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 重新启用 CRM 菜单
-- ----------------------------
UPDATE `la_system_menu`
SET `is_show` = 1,
    `is_disable` = 0,
    `update_time` = UNIX_TIMESTAMP()
WHERE (
      (`type` = 'M' AND `paths` = 'crm')
      OR `perms` IN (
          'crm.customer/lists',
          'crm.sales_advisor/lists',
          'crm.customer_loss_warning/lists'
      )
      OR `component` IN (
          'crm/customer/index',
          'crm/advisor/index',
          'crm/warning/index'
      )
  );

-- ----------------------------
-- 给“管理员”角色补齐 CRM 菜单授权
-- ----------------------------
SET @admin_role_id = (
    SELECT `id`
    FROM `la_system_role`
    WHERE `name` = '管理员'
      AND `delete_time` IS NULL
    ORDER BY `id` ASC
    LIMIT 1
);

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @admin_role_id, m.`id`
FROM `la_system_menu` m
WHERE @admin_role_id IS NOT NULL
  AND (
      (`type` = 'M' AND `paths` = 'crm')
      OR `perms` IN (
          'crm.customer/lists',
          'crm.sales_advisor/lists',
          'crm.customer_loss_warning/lists'
      )
      OR `component` IN (
          'crm/customer/index',
          'crm/advisor/index',
          'crm/warning/index'
      )
  )
  AND NOT EXISTS (
      SELECT 1
      FROM `la_system_role_menu` rm
      WHERE rm.`role_id` = @admin_role_id
        AND rm.`menu_id` = m.`id`
  );

SET FOREIGN_KEY_CHECKS = 1;
