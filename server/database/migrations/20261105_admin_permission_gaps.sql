-- 后台操作权限补登记（2026-11-05 全栈审计）：只添加权限项，不自动向现有角色授予任何权限。
--
-- 背景：AuthMiddleware 对“未登记接口”一律拒绝，而这些接口已被后台页面调用，
-- 导致非 root 管理员点击对应功能时收到“权限未登记，无法访问或操作”。
-- 本迁移只登记权限点（type=A，挂在既有菜单下，不新增导航项），
-- 管理员仍需在“角色管理”里按岗位显式勾选后才会生效。

-- 收款记录 / 支付流水（入口在订单列表与退款详情内）
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '收款记录列表', 'ops.payment/lists', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.payment/lists');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '收款记录详情', 'ops.payment/detail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.payment/detail');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '收款记录统计', 'ops.payment/statistics', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.payment/statistics');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '收款记录状态选项', 'ops.payment/statusOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.payment/statusOptions');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '收款记录类型选项', 'ops.payment/typeOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.payment/typeOptions');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '收款记录方式选项', 'ops.payment/wayOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.payment/wayOptions');

-- 档期确认函（入口在订单详情抽屉内）
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '生成档期确认函', 'ops.order/confirmLetterGenerate', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/confirmLetterGenerate');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '档期确认函详情', 'ops.order/confirmLetterDetail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/confirmLetterDetail');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '档期确认函历史', 'ops.order/confirmLetterHistory', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/confirmLetterHistory');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '档期确认函素材', 'ops.order/confirmLetterAssets', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/confirmLetterAssets');

-- 退款重试（退款管理页）
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '退款重试', 'ops.refund/retry', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.refund/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.refund/retry');

-- 评价标签（评价列表页的标签管理）
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '评价标签列表', 'growth.reviewTag/lists', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.reviewTag/lists');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '评价标签详情', 'growth.reviewTag/detail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.reviewTag/detail');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增评价标签', 'growth.reviewTag/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.reviewTag/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑评价标签', 'growth.reviewTag/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.reviewTag/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除评价标签', 'growth.reviewTag/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.reviewTag/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '评价标签状态', 'growth.reviewTag/status', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.reviewTag/status');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '评价标签分组', 'growth.reviewTag/grouped', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.reviewTag/grouped');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '评价标签类型选项', 'growth.reviewTag/typeOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.reviewTag/typeOptions');

-- 敏感词管理（内容审核）
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '敏感词列表', 'growth.sensitiveWord/lists', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/lists');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '敏感词详情', 'growth.sensitiveWord/detail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/detail');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增敏感词', 'growth.sensitiveWord/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑敏感词', 'growth.sensitiveWord/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除敏感词', 'growth.sensitiveWord/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量删除敏感词', 'growth.sensitiveWord/batchDelete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/batchDelete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '敏感词状态', 'growth.sensitiveWord/status', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/status');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '导入敏感词', 'growth.sensitiveWord/import', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/import');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '敏感词检测', 'growth.sensitiveWord/check', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/check');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '敏感词类型选项', 'growth.sensitiveWord/typeOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/typeOptions');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '敏感词级别选项', 'growth.sensitiveWord/levelOptions', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.sensitiveWord/levelOptions');

-- 公众号模板同步（消息通知页）
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '拉取公众号模板', 'notification.oaNotification/getWechatTemplates', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'notification.oaNotification/templateList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'notification.oaNotification/getWechatTemplates');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '同步公众号模板', 'notification.oaNotification/syncTemplates', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'notification.oaNotification/templateList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'notification.oaNotification/syncTemplates');
