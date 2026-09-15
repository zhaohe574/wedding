-- 后台操作权限补登记：只添加权限项，不自动向现有角色授予写入权限。
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'ops.order/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'ops.order/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '取消', 'ops.order/cancel', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/cancel');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '确认', 'ops.order/confirm', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/confirm');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '开始服务', 'ops.order/startService', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/startService');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '完成服务', 'ops.order/complete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/complete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '直接改期', 'ops.order/directReschedule', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/directReschedule');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'ops.order/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '确认线下收款', 'ops.order/confirmOfflinePay', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/confirmOfflinePay');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核付款凭证', 'ops.order/auditVoucher', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/auditVoucher');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '添加备注', 'ops.order/addRemark', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/addRemark');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '线下建单', 'ops.order/addOffline', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.order/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.order/addOffline');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核', 'ops.orderChange/audit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.orderChange/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.orderChange/audit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '执行', 'ops.orderChange/execute', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.orderChange/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.orderChange/execute');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '申请', 'ops.refund/apply', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.refund/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.refund/apply');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核', 'ops.refund/audit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.refund/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.refund/audit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '确认退款', 'ops.refund/confirmRefund', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.refund/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.refund/confirmRefund');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'ops.category/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.category/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.category/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'ops.category/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.category/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.category/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'ops.category/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.category/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.category/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换状态', 'ops.category/changeStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.category/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.category/changeStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'ops.package/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.package/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.package/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'ops.package/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.package/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.package/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'ops.package/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.package/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.package/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换状态', 'ops.package/changeStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.package/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.package/changeStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'ops.staff/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'ops.staff/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'ops.staff/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换状态', 'ops.staff/changeStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/changeStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '配置套餐', 'ops.staff/configurePackages', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/configurePackages');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增人员附加服务', 'ops.staff/createStaffAddon', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/createStaffAddon');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增人员套餐', 'ops.staff/createStaffPackage', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/createStaffPackage');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除人员附加服务', 'ops.staff/deleteStaffAddon', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/deleteStaffAddon');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除人员套餐', 'ops.staff/deleteStaffPackage', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/deleteStaffPackage');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增轮播图', 'ops.staff/addBanner', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/addBanner');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑轮播图', 'ops.staff/editBanner', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/editBanner');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除轮播图', 'ops.staff/deleteBanner', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/deleteBanner');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '轮播图排序', 'ops.staff/sortBanner', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/sortBanner');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改轮播图配置', 'ops.staff/updateBannerConfig', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/updateBannerConfig');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改套餐配置', 'ops.staff/updatePackageConfig', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/updatePackageConfig');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改人员附加服务', 'ops.staff/updateStaffAddon', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/updateStaffAddon');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改人员套餐', 'ops.staff/updateStaffPackage', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/updateStaffPackage');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '重置后台密码', 'ops.staff/resetAdminPassword', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.staff/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.staff/resetAdminPassword');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量设置档期', 'ops.schedule/batchSet', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/batchSet');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '锁定档期', 'ops.schedule/lock', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/lock');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '解除锁定', 'ops.schedule/unlock', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/unlock');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '预留档期', 'ops.schedule/reserve', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/reserve');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '修改状态', 'ops.schedule/setStatus', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.schedule/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.schedule/setStatus');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量通知', 'ops.waitlist/batchNotify', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.waitlist/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.waitlist/batchNotify');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '候补转预约', 'ops.waitlist/convert', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.waitlist/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.waitlist/convert');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '作废候补', 'ops.waitlist/invalidate', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.waitlist/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.waitlist/invalidate');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '发送候补通知', 'ops.waitlist/notify', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'ops.waitlist/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'ops.waitlist/notify');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'growth.dynamic/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'growth.dynamic/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'growth.dynamic/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除评论', 'growth.dynamic/deleteComment', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/deleteComment');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '置顶评论', 'growth.dynamic/setCommentTop', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/setCommentTop');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '下架', 'growth.dynamic/offline', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/offline');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '设置热门', 'growth.dynamic/setHot', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/setHot');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '置顶', 'growth.dynamic/setTop', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamic/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamic/setTop');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '通过', 'growth.dynamicComment/approve', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/approve');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量通过', 'growth.dynamicComment/batchApprove', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/batchApprove');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量删除', 'growth.dynamicComment/batchDelete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/batchDelete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量拒绝', 'growth.dynamicComment/batchReject', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/batchReject');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'growth.dynamicComment/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '拒绝', 'growth.dynamicComment/reject', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.dynamicComment/reviewList' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.dynamicComment/reject');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核', 'growth.review/audit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/audit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量审核', 'growth.review/batchAudit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/batchAudit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'growth.review/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '回复', 'growth.review/reply', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/reply');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换展示', 'growth.review/toggleShow', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/toggleShow');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换置顶', 'growth.review/toggleTop', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.review/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.review/toggleTop');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量删除', 'growth.notification/batchDelete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/batchDelete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量发送', 'growth.notification/batchSend', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/batchSend');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'growth.notification/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '发送通知', 'growth.notification/send', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/send');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '全体通知', 'growth.notification/sendToAll', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'growth.notification/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'growth.notification/sendToAll');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '审核结算批次', 'finance.settlement/auditBatch', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/auditBatch');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '批量结算', 'finance.settlement/batchSettle', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/batchSettle');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '取消', 'finance.settlement/cancel', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/cancel');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '取消结算批次', 'finance.settlement/cancelBatch', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/cancelBatch');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '创建结算批次', 'finance.settlement/createBatch', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/createBatch');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '执行结算批次', 'finance.settlement/executeBatch', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/executeBatch');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '发起结算', 'finance.settlement/settle', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'finance.settlement/settle');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '新增', 'channel.official_account_reply/add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'channel.official_account_reply/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'channel.official_account_reply/add');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'channel.official_account_reply/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'channel.official_account_reply/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'channel.official_account_reply/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '删除', 'channel.official_account_reply/delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'channel.official_account_reply/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'channel.official_account_reply/delete');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '切换状态', 'channel.official_account_reply/status', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'channel.official_account_reply/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'channel.official_account_reply/status');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '编辑', 'article.articleCate/edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'article.articleCate/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'article.articleCate/edit');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '保存通知配置', 'notification.oaNotification/saveConfig', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'notification.oaNotification/config' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'notification.oaNotification/saveConfig');
-- 账号关系属于独立写权限，部署后按岗位显式授权。
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '管理账号绑定', 'auth.admin/bindingSave', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'auth.admin/lists' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'auth.admin/bindingSave');
INSERT INTO `la_system_menu` (`pid`,`type`,`name`,`perms`,`create_time`,`update_time`)
SELECT MIN(`id`), 'A', '查看账号绑定', 'auth.admin/bindingDetail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM `la_system_menu`
WHERE `perms` = 'auth.admin/bindingSave' HAVING MIN(`id`) IS NOT NULL AND NOT EXISTS (SELECT 1 FROM (SELECT `perms` FROM `la_system_menu`) AS existing WHERE existing.`perms` = 'auth.admin/bindingDetail');
