-- 后台操作权限补登记（B3 功能补齐）：只登记权限点，不自动授予任何角色。
--
-- 背景：AuthMiddleware 对「未登记接口」一律拒绝。B3 新增 4 个页面：
--   订单与履约 › 暂停审核（order.orderPause/*）
--   服务与内容 › 附加项（service.addon/*）
--   设置 › 客服与咨询（setting.customerService/*）
--   工作台 › 经营驾驶舱（dashboard.dashboard/*）
-- 不登记的话，非 root 管理员点进去就是 403「权限未登记」。
-- 权限标识必须与控制器 action 完全一致：controller/order/OrderPauseController::audit() => order.orderPause/audit

-- ---- order.orderPause/lists ----
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '暂停申请列表', 'order.orderPause/lists', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/lists');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '暂停申请详情', 'order.orderPause/detail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/detail');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '暂停审核', 'order.orderPause/audit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/audit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '恢复服务', 'order.orderPause/resume', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/resume');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '延长暂停', 'order.orderPause/extend', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/extend');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '暂停日志', 'order.orderPause/logs', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/logs');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '即将到期列表', 'order.orderPause/expiring', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/expiring');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '暂停统计', 'order.orderPause/statistics', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/statistics');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '暂停类型选项', 'order.orderPause/typeOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/typeOptions');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '暂停状态选项', 'order.orderPause/statusOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'order.orderPause/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'order.orderPause/statusOptions');

-- ---- service.addon/lists ----
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '附加项列表', 'service.addon/lists', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'service.addon/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'service.addon/lists');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '附加项详情', 'service.addon/detail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'service.addon/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'service.addon/detail');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增附加项', 'service.addon/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'service.addon/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'service.addon/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑附加项', 'service.addon/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'service.addon/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'service.addon/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除附加项', 'service.addon/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'service.addon/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'service.addon/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '附加项上下架', 'service.addon/changeStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'service.addon/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'service.addon/changeStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '附加项下拉', 'service.addon/all', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'service.addon/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'service.addon/all');

-- ---- setting.customerService/getConfig ----
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '读取客服配置', 'setting.customerService/getConfig', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'setting.customerService/getConfig' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'setting.customerService/getConfig');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '保存客服配置', 'setting.customerService/setConfig', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'setting.customerService/getConfig' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'setting.customerService/setConfig');

-- ---- dashboard.dashboard/overview ----
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '经营看板-财务概览', 'dashboard.dashboard/overview', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'dashboard.dashboard/overview' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'dashboard.dashboard/overview');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '经营看板-收入趋势', 'dashboard.dashboard/incomeTrend', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'dashboard.dashboard/overview' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'dashboard.dashboard/incomeTrend');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '经营看板-订单统计', 'dashboard.dashboard/orderStats', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'dashboard.dashboard/overview' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'dashboard.dashboard/orderStats');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '经营看板-团队总览', 'dashboard.dashboard/teamOverview', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'dashboard.dashboard/overview' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'dashboard.dashboard/teamOverview');

