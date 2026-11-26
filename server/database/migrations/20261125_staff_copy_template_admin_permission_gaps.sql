-- 后台操作权限补登记（工作台话术模板后台可配置化）：只登记权限点，不自动授予任何角色。
--
-- 背景：AuthMiddleware 对「未登记接口」一律拒绝。本次新增「工作台 › 工作台话术模板」页面：
--   工作台 › 工作台话术模板（ops.staffCopyTemplate/*）
-- 不登记的话，非 root 管理员点进去就是 403「权限未登记」。
-- 权限标识必须与控制器 action 完全一致：controller/staff/StaffCopyTemplateController::lists() => staff.staffCopyTemplate/lists（ops 别名同步登记）

-- ---- ops.staffCopyTemplate/lists ----
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '工作台统一话术列表', 'ops.staffCopyTemplate/lists', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staffCopyTemplate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staffCopyTemplate/lists');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '工作台统一话术详情', 'ops.staffCopyTemplate/detail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staffCopyTemplate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staffCopyTemplate/detail');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增统一话术', 'ops.staffCopyTemplate/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staffCopyTemplate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staffCopyTemplate/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑统一话术', 'ops.staffCopyTemplate/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staffCopyTemplate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staffCopyTemplate/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除统一话术', 'ops.staffCopyTemplate/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staffCopyTemplate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staffCopyTemplate/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '启停统一话术', 'ops.staffCopyTemplate/changeStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staffCopyTemplate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staffCopyTemplate/changeStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '话术场景与变量选项', 'ops.staffCopyTemplate/sceneOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staffCopyTemplate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staffCopyTemplate/sceneOptions');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '导入系统默认话术', 'ops.staffCopyTemplate/importDefaults', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staffCopyTemplate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staffCopyTemplate/importDefaults');
