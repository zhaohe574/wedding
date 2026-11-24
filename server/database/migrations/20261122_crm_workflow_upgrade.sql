-- 客户与跟进流程升级：新增幂等按钮权限节点
-- 日期：2026-11-22

-- ---- crm.customer/add（挂在 page.crm.customer 菜单下，依据 crm.customer/lists 查找父菜单）----
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `perms`, `create_time`, `update_time`)
SELECT MIN(`pid`), 'A', '新增客户', 'crm.customer/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'crm.customer/edit'
HAVING MIN(`pid`) IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'crm.customer/add');

-- ---- crm.customer/batchTransfer ----
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `perms`, `create_time`, `update_time`)
SELECT MIN(`pid`), 'A', '批量转移顾问', 'crm.customer/batchTransfer', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'crm.customer/transferAdvisor'
HAVING MIN(`pid`) IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'crm.customer/batchTransfer');

-- ---- crm.salesAdvisor/handover ----
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `perms`, `create_time`, `update_time`)
SELECT MIN(`pid`), 'A', '顾问客户交接', 'crm.salesAdvisor/handover', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'crm.salesAdvisor/changeStatus'
HAVING MIN(`pid`) IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'crm.salesAdvisor/handover');

-- ---- crm.salesAdvisor/syncAllCustomerCount ----
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `perms`, `create_time`, `update_time`)
SELECT MIN(`pid`), 'A', '全量校准顾问负载', 'crm.salesAdvisor/syncAllCustomerCount', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'crm.salesAdvisor/syncCustomerCount'
HAVING MIN(`pid`) IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'crm.salesAdvisor/syncAllCustomerCount');
