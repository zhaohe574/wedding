-- =============================================
-- 后台菜单重构迁移脚本（全后台范围）
-- 文件名：menu_migration.sql
-- 说明：
-- 1) 重建一级菜单分组（两级优先）
-- 2) 按业务域迁移二级菜单（C）
-- 3) 合并归档遗留一级菜单（不删库，仅隐藏）
-- 4) 生成回滚快照（供 rollback_snapshot.sql 使用）
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- 0. 快照（幂等：每次执行都会覆盖快照内容）
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `la_system_menu_snapshot_admin_refactor_20260304` LIKE `la_system_menu`;
TRUNCATE TABLE `la_system_menu_snapshot_admin_refactor_20260304`;
INSERT INTO `la_system_menu_snapshot_admin_refactor_20260304` SELECT * FROM `la_system_menu`;

CREATE TABLE IF NOT EXISTS `la_system_role_menu_snapshot_admin_refactor_20260304` LIKE `la_system_role_menu`;
TRUNCATE TABLE `la_system_role_menu_snapshot_admin_refactor_20260304`;
INSERT INTO `la_system_role_menu_snapshot_admin_refactor_20260304` SELECT * FROM `la_system_role_menu`;

CREATE TABLE IF NOT EXISTS `la_system_role_snapshot_admin_refactor_20260304` LIKE `la_system_role`;
TRUNCATE TABLE `la_system_role_snapshot_admin_refactor_20260304`;
INSERT INTO `la_system_role_snapshot_admin_refactor_20260304` SELECT * FROM `la_system_role`;

-- ---------------------------------------------
-- 1. 新一级菜单骨架（M）
-- ---------------------------------------------
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','工作台','el-icon-DataBoard',1200,'','workbench','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'workbench');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','履约运营','el-icon-Operation',1100,'','ops','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'ops');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','客户增长','el-icon-TrendCharts',1050,'','growth','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'growth');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','财务结算','el-icon-Coin',1000,'','finance-settlement','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'finance-settlement');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','用户与内容','el-icon-User',950,'','content','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'content');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','渠道与装修','el-icon-Brush',900,'','experience','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'experience');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','平台配置','el-icon-Setting',850,'','platform','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'platform');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','组织权限','el-icon-Lock',800,'','org-auth','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'org-auth');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','系统工具','el-icon-Tools',750,'','system-tools','','','',0,0,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'system-tools');

-- staff_center -> staff-center（统一 kebab-case）
UPDATE `la_system_menu`
SET `paths` = 'staff-center', `name` = '服务人员中心', `sort` = 700, `icon` = 'el-icon-User', `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = 0 AND `type` = 'M' AND `paths` IN ('staff_center', 'staff-center');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT 0,'M','服务人员中心','el-icon-User',700,'','staff-center','','','',0,1,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'staff-center');

-- 若历史数据中出现多个 staff-center 根菜单，统一挂载到最新节点，并归档其余重复节点
SET @root_staff_center_keep_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `pid` = 0 AND `type` = 'M' AND `paths` = 'staff-center'
    ORDER BY `id` DESC
    LIMIT 1
);

UPDATE `la_system_menu` c
JOIN `la_system_menu` p ON p.`id` = c.`pid`
SET c.`pid` = @root_staff_center_keep_id, c.`update_time` = UNIX_TIMESTAMP()
WHERE c.`type` = 'C'
  AND p.`pid` = 0
  AND p.`type` = 'M'
  AND p.`paths` = 'staff-center'
  AND p.`id` <> @root_staff_center_keep_id;

UPDATE `la_system_menu`
SET `is_show` = 0, `is_disable` = 1, `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = 0
  AND `type` = 'M'
  AND `paths` = 'staff-center'
  AND `id` <> @root_staff_center_keep_id;

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
-- 2. 一级「工作台」：运营总览
-- ---------------------------------------------
UPDATE `la_system_menu`
SET `pid` = @root_workbench_id, `name` = '运营总览', `paths` = 'overview', `sort` = 100, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C' AND `component` = 'workbench/index';

-- ---------------------------------------------
-- 3. 域迁移（按 component/perms 双条件）
-- ---------------------------------------------
-- 履约运营
UPDATE `la_system_menu`
SET `pid` = @root_ops_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      (`component` LIKE 'staff/%' AND `component` NOT LIKE 'staff_center/%')
      OR `component` LIKE 'service/%'
      OR `component` LIKE 'schedule/%'
      OR `component` LIKE 'order/%'
      OR `component` LIKE 'aftersale/%'
      OR `perms` LIKE 'staff.%/%'
      OR `perms` LIKE 'service.%/%'
      OR `perms` LIKE 'schedule.%/%'
      OR `perms` LIKE 'order.%/%'
      OR `perms` LIKE 'aftersale.%/%'
  );

-- 客户增长
UPDATE `la_system_menu`
SET `pid` = @root_growth_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `component` LIKE 'crm/%'
      OR `component` LIKE 'dynamic/%'
      OR `component` LIKE 'review/%'
      OR `component` LIKE 'coupon/%'
      OR `component` LIKE 'notification/%'
      OR `component` LIKE 'subscribe/%'
      OR `component` LIKE 'timeline/%'
      OR `perms` LIKE 'crm.%/%'
      OR `perms` LIKE 'dynamic.%/%'
      OR `perms` LIKE 'review.%/%'
      OR `perms` LIKE 'coupon.%/%'
      OR `perms` LIKE 'notification.%/%'
      OR `perms` LIKE 'subscribe.%/%'
      OR `perms` LIKE 'timeline.%/%'
  );

-- 财务结算
UPDATE `la_system_menu`
SET `pid` = @root_finance_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `component` LIKE 'financial/%'
      OR `component` LIKE 'finance/%'
      OR `component` = 'app/recharge/index'
      OR `perms` LIKE 'financial.%/%'
      OR `perms` LIKE 'finance.%/%'
      OR `perms` LIKE 'recharge.recharge/%'
  );

-- 用户与内容
UPDATE `la_system_menu`
SET `pid` = @root_content_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `component` LIKE 'consumer/%'
      OR `component` LIKE 'article/%'
      OR `component` = 'material/index'
      OR `perms` LIKE 'user.user/%'
      OR `perms` LIKE 'article.%/%'
      OR `perms` = 'file/listCate'
  );

-- 渠道与装修
UPDATE `la_system_menu`
SET `pid` = @root_experience_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `component` LIKE 'channel/%'
      OR `component` LIKE 'decoration/%'
      OR `perms` LIKE 'channel.%/%'
      OR `perms` LIKE 'decorate.%/%'
  );

-- 平台配置
UPDATE `la_system_menu`
SET `pid` = @root_platform_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `component` LIKE 'setting/%'
      OR `perms` LIKE 'setting.%/%'
      OR `perms` LIKE 'crontab.%/%'
  );

-- 组织权限
UPDATE `la_system_menu`
SET `pid` = @root_org_auth_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `component` LIKE 'organization/%'
      OR `component` LIKE 'permission/%'
      OR `perms` LIKE 'auth.%/%'
      OR `perms` LIKE 'dept.%/%'
  );

-- 系统工具
UPDATE `la_system_menu`
SET `pid` = @root_tools_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `component` LIKE 'dev_tools/%'
      OR `component` LIKE 'template/%'
  );

-- 服务人员中心
UPDATE `la_system_menu`
SET `pid` = @root_staff_center_id, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `component` LIKE 'staff_center/%'
      OR `perms` LIKE 'staff.staff/my%'
      OR `perms` LIKE 'schedule.schedule/my%'
      OR `perms` LIKE 'schedule.scheduleRule/my%'
      OR `perms` LIKE 'schedule.booking/my%'
      OR `perms` LIKE 'schedule.waitlist/my%'
      OR `perms` LIKE 'order.order/my%'
      OR `perms` LIKE 'dynamic.dynamic/my%'
      OR `perms` LIKE 'ops.staff/my%'
      OR `perms` LIKE 'ops.schedule/my%'
      OR `perms` LIKE 'ops.scheduleRule/my%'
      OR `perms` LIKE 'ops.booking/my%'
      OR `perms` LIKE 'ops.waitlist/my%'
      OR `perms` LIKE 'ops.order/my%'
      OR `perms` LIKE 'growth.dynamic/my%'
  );

-- ---------------------------------------------
-- 4. 核心二级菜单标准化（名称/路径/组件/排序）
-- ---------------------------------------------
-- 履约运营
UPDATE `la_system_menu` SET `name` = '服务人员', `paths` = 'staff', `component` = 'staff/lists/index', `sort` = 200, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'staff.staff/lists';
UPDATE `la_system_menu` SET `name` = '作品审核', `paths` = 'work-review', `component` = 'staff/work/index', `sort` = 190, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'staff.work/lists';
UPDATE `la_system_menu` SET `name` = '服务分类', `paths` = 'service-category', `component` = 'service/category/index', `sort` = 180, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'service.service_category/lists';
UPDATE `la_system_menu` SET `name` = '服务套餐', `paths` = 'service-package', `component` = 'service/package/index', `sort` = 170, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'service.service_package/lists';
UPDATE `la_system_menu` SET `name` = '风格标签', `paths` = 'style-tag', `component` = 'service/tag/index', `sort` = 160, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` IN ('service.styleTag/lists', 'service.style_tag/lists');
UPDATE `la_system_menu` SET `name` = '档期日历', `paths` = 'schedule-calendar', `component` = 'schedule/calendar/index', `sort` = 150, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.schedule/lists';
UPDATE `la_system_menu` SET `name` = '档期规则', `paths` = 'schedule-rule', `component` = 'schedule/rule/index', `sort` = 140, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.scheduleRule/lists';
UPDATE `la_system_menu` SET `name` = '预约管理', `paths` = 'booking-manage', `component` = 'schedule/booking/index', `sort` = 130, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.booking/lists';
UPDATE `la_system_menu` SET `name` = '候补管理', `paths` = 'waitlist-manage', `component` = 'schedule/waitlist/index', `sort` = 120, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.waitlist/lists';
UPDATE `la_system_menu` SET `name` = '吉日管理', `paths` = 'lucky-day', `component` = 'schedule/event/index', `sort` = 110, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.calendarEvent/lists';
UPDATE `la_system_menu` SET `name` = '订单管理', `paths` = 'orders', `component` = 'order/lists/index', `sort` = 100, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'order.order/lists';
UPDATE `la_system_menu` SET `name` = '订单变更', `paths` = 'order-change', `component` = 'order/change/index', `sort` = 90, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'order.order_change/lists';
UPDATE `la_system_menu` SET `name` = '订单转让', `paths` = 'order-transfer', `component` = 'order/transfer/index', `sort` = 80, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'order.order_transfer/lists';
UPDATE `la_system_menu` SET `name` = '订单暂停', `paths` = 'order-pause', `component` = 'order/pause/index', `sort` = 70, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'order.order_pause/lists';
UPDATE `la_system_menu` SET `name` = '退款管理', `paths` = 'refund-manage', `component` = 'order/refund/index', `sort` = 60, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'order.refund/lists';
UPDATE `la_system_menu` SET `name` = '售后工单', `paths` = 'aftersale-ticket', `component` = 'aftersale/ticket/index', `sort` = 50, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'aftersale.aftersale/ticketLists';

-- 客户增长
UPDATE `la_system_menu` SET `name` = '客户管理', `paths` = 'customers', `component` = 'crm/customer/index', `sort` = 200, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'crm.customer/lists';
UPDATE `la_system_menu` SET `name` = '顾问管理', `paths` = 'advisors', `component` = 'crm/advisor/index', `sort` = 190, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'crm.sales_advisor/lists';
UPDATE `la_system_menu` SET `name` = '流失预警', `paths` = 'loss-warning', `component` = 'crm/warning/index', `sort` = 180, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'crm.customer_loss_warning/lists';
UPDATE `la_system_menu` SET `name` = '营销活动', `paths` = 'campaigns', `component` = 'coupon/lists/index', `sort` = 170, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'coupon.coupon/lists';
UPDATE `la_system_menu` SET `name` = '动态管理', `paths` = 'dynamic-manage', `component` = 'dynamic/lists/index', `sort` = 160, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'dynamic.dynamic/lists';
UPDATE `la_system_menu` SET `name` = '评论审核', `paths` = 'comment-review', `component` = 'dynamic/comment/review', `sort` = 150, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'dynamic.dynamicComment/reviewList';
UPDATE `la_system_menu` SET `name` = '动态配置', `paths` = 'dynamic-config', `component` = 'dynamic/config/index', `sort` = 140, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'dynamic.dynamicComment/getReviewConfig';
UPDATE `la_system_menu` SET `name` = '评价管理', `paths` = 'review-manage', `component` = 'review/lists/index', `sort` = 130, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'review.review/lists';
UPDATE `la_system_menu` SET `name` = '评价申诉', `paths` = 'review-appeal', `component` = 'review/appeal/index', `sort` = 120, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` IN ('review.reviewAppeal/lists', 'review.review_appeal/lists');
UPDATE `la_system_menu` SET `name` = '评价标签', `paths` = 'review-tags', `component` = 'review/tag/index', `sort` = 110, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` IN ('review.reviewTag/lists', 'review.review_tag/lists');
UPDATE `la_system_menu` SET `name` = '敏感词', `paths` = 'sensitive-words', `component` = 'review/sensitive/index', `sort` = 100, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` IN ('review.sensitiveWord/lists', 'review.sensitive_word/lists');
UPDATE `la_system_menu` SET `name` = '消息通知', `paths` = 'message-notice', `component` = 'notification/lists/index', `sort` = 90, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'notification.notification/lists';
UPDATE `la_system_menu` SET `name` = '订阅消息', `paths` = 'subscribe-message', `component` = 'subscribe/template/index', `sort` = 80, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'subscribe.subscribe/templateList';
UPDATE `la_system_menu` SET `name` = '时间线模板', `paths` = 'timeline-template', `component` = 'timeline/lists/index', `sort` = 70, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'timeline.timeline/templateList';

-- 财务结算
UPDATE `la_system_menu` SET `name` = '财务概览', `paths` = 'overview', `component` = 'financial/overview/index', `sort` = 200, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` IN ('financial.overview/index', 'financial.financial_report/overview');
UPDATE `la_system_menu` SET `name` = '资金流水', `paths` = 'flow', `component` = 'financial/flow/index', `sort` = 190, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'financial.flow/lists';
UPDATE `la_system_menu` SET `name` = '结算管理', `paths` = 'settlement', `component` = 'financial/settlement/index', `sort` = 180, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'financial.settlement/lists';
UPDATE `la_system_menu` SET `name` = '成本管理', `paths` = 'cost', `component` = 'financial/cost/index', `sort` = 170, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'financial.cost/lists';
UPDATE `la_system_menu` SET `name` = '发票管理', `paths` = 'invoice', `component` = 'financial/invoice/index', `sort` = 160, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'financial.invoice/lists';
UPDATE `la_system_menu` SET `name` = '充值记录', `paths` = 'recharge-record', `component` = 'finance/recharge_record', `sort` = 150, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'recharge.recharge/lists';
UPDATE `la_system_menu` SET `name` = '余额明细', `paths` = 'balance-details', `component` = 'finance/balance_details', `sort` = 140, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'finance.account_log/lists';
UPDATE `la_system_menu` SET `name` = '退款记录', `paths` = 'refund-record', `component` = 'finance/refund_record', `sort` = 130, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'finance.refund/record';
UPDATE `la_system_menu` SET `name` = '充值配置', `paths` = 'recharge-config', `component` = 'app/recharge/index', `sort` = 120, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'recharge.recharge/getConfig';

-- 用户与内容
UPDATE `la_system_menu` SET `name` = '用户列表', `paths` = 'users', `component` = 'consumer/lists/index', `sort` = 200, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'user.user/lists';
UPDATE `la_system_menu` SET `name` = '文章分类', `paths` = 'article-category', `component` = 'article/column/index', `sort` = 190, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'article/column/index';
UPDATE `la_system_menu` SET `name` = '文章列表', `paths` = 'article-list', `component` = 'article/lists/index', `sort` = 180, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'article/lists/index';
UPDATE `la_system_menu` SET `name` = '素材中心', `paths` = 'materials', `component` = 'material/index', `sort` = 170, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'material/index';

-- 渠道与装修
UPDATE `la_system_menu` SET `name` = 'H5设置', `paths` = 'h5-setting', `sort` = 200, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'channel/h5';
UPDATE `la_system_menu` SET `name` = '微信小程序', `paths` = 'weapp-setting', `sort` = 190, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'channel/weapp';
UPDATE `la_system_menu` SET `name` = '公众号配置', `paths` = 'oa-config', `sort` = 180, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'channel/wx_oa/config';
UPDATE `la_system_menu` SET `name` = '公众号菜单', `paths` = 'oa-menu', `sort` = 170, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'channel/wx_oa/menu';
UPDATE `la_system_menu` SET `name` = '开放平台', `paths` = 'open-platform', `sort` = 160, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'channel/open_setting';
UPDATE `la_system_menu` SET `name` = '移动端页面装修', `paths` = 'mobile-page-decoration', `sort` = 150, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'decoration/pages/index';
UPDATE `la_system_menu` SET `name` = '移动端底部导航', `paths` = 'mobile-tabbar', `sort` = 140, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'decoration/tabbar';
UPDATE `la_system_menu` SET `name` = '系统风格', `paths` = 'system-style', `sort` = 130, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'decoration/style/style';
UPDATE `la_system_menu` SET `name` = 'PC端装修', `paths` = 'pc-decoration', `sort` = 120, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'decoration/pc';

-- 组织权限
UPDATE `la_system_menu` SET `name` = '部门管理', `paths` = 'departments', `sort` = 200, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'organization/department/index';
UPDATE `la_system_menu` SET `name` = '岗位管理', `paths` = 'posts', `sort` = 190, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'organization/post/index';
UPDATE `la_system_menu` SET `name` = '管理员', `paths` = 'admins', `sort` = 180, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'permission/admin/index';
UPDATE `la_system_menu` SET `name` = '角色', `paths` = 'roles', `sort` = 170, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'permission/role/index';
UPDATE `la_system_menu` SET `name` = '菜单权限', `paths` = 'menus', `sort` = 160, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'permission/menu/index';

-- 系统工具
UPDATE `la_system_menu` SET `name` = '代码生成', `paths` = 'code-generator', `sort` = 200, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` = 'dev_tools/code/index';
UPDATE `la_system_menu` SET `name` = '模板示例', `paths` = 'template-samples', `sort` = 190, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `component` LIKE 'template/component/%';

-- 服务人员中心
UPDATE `la_system_menu` SET `name` = '我的资料', `paths` = 'profile', `component` = 'staff_center/profile/index', `sort` = 200, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'staff.staff/myProfile';
UPDATE `la_system_menu` SET `name` = '档期日历', `paths` = 'calendar', `component` = 'staff_center/calendar/index', `sort` = 190, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.schedule/myCalendar';
UPDATE `la_system_menu` SET `name` = '档期规则', `paths` = 'rule', `component` = 'staff_center/rule/index', `sort` = 180, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.scheduleRule/myRules';
UPDATE `la_system_menu` SET `name` = '预约列表', `paths` = 'booking', `component` = 'staff_center/booking/index', `sort` = 170, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.booking/myBookings';
UPDATE `la_system_menu` SET `name` = '候补列表', `paths` = 'waitlist', `component` = 'staff_center/waitlist/index', `sort` = 160, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'schedule.waitlist/myWaitlist';
UPDATE `la_system_menu` SET `name` = '订单列表', `paths` = 'orders', `component` = 'staff_center/order/index', `sort` = 150, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'order.order/myOrders';
UPDATE `la_system_menu` SET `name` = '动态管理', `paths` = 'dynamic', `component` = 'staff_center/dynamic/index', `sort` = 140, `update_time` = UNIX_TIMESTAMP() WHERE `type` = 'C' AND `perms` = 'dynamic.dynamic/myDynamics';

-- ---------------------------------------------
-- 5. 合并/下线规则
-- ---------------------------------------------
-- 售后“投诉/补拍/回访”不在侧栏单独暴露（一期保留在售后工单页签内）
UPDATE `la_system_menu`
SET `is_show` = 0, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C' AND `perms` IN ('aftersale.complaint/lists', 'aftersale.reshoot/lists', 'aftersale.callback/lists');

-- crm/follow 页面未落地，先隐藏
UPDATE `la_system_menu`
SET `is_show` = 0, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C' AND (`perms` = 'crm.followRecord/lists' OR `component` = 'crm/follow/index');

-- 精简版一期下线后台扩展入口
UPDATE `la_system_menu`
SET `is_show` = 0, `is_disable` = 1, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `perms` IN (
          'schedule.calendarEvent/lists',
          'aftersale.aftersale/ticketLists',
          'crm.customer/lists',
          'crm.sales_advisor/lists',
          'crm.customer_loss_warning/lists',
          'crm.followRecord/lists',
          'timeline.timeline/templateList',
          'financial.cost/lists',
          'financial.invoice/lists'
      )
      OR `component` IN (
          'schedule/event/index',
          'aftersale/ticket/index',
          'crm/customer/index',
          'crm/advisor/index',
          'crm/warning/index',
          'crm/follow/index',
          'timeline/lists/index',
          'financial/cost/index',
          'financial/invoice/index'
      )
  );

-- 新增/编辑/详情保持隐藏
UPDATE `la_system_menu`
SET `is_show` = 0, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (
      `perms` LIKE '%/add:edit'
      OR `paths` LIKE '%/edit%'
      OR `component` LIKE '%/edit%'
      OR `paths` LIKE '%/detail%'
      OR `component` LIKE '%/detail%'
  );

-- ---------------------------------------------
-- 6. selected 路径重写（隐藏页高亮修正）
-- ---------------------------------------------
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/service/', '/ops/') WHERE `selected` LIKE '/service/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/schedule/', '/ops/') WHERE `selected` LIKE '/schedule/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/order/', '/ops/') WHERE `selected` LIKE '/order/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/aftersale/', '/ops/') WHERE `selected` LIKE '/aftersale/%';

UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/crm/', '/growth/') WHERE `selected` LIKE '/crm/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/dynamic/', '/growth/') WHERE `selected` LIKE '/dynamic/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/review/', '/growth/') WHERE `selected` LIKE '/review/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/message/', '/growth/') WHERE `selected` LIKE '/message/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/timeline/', '/growth/') WHERE `selected` LIKE '/timeline/%';

UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/financial/', '/finance-settlement/') WHERE `selected` LIKE '/financial/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/finance/', '/finance-settlement/') WHERE `selected` LIKE '/finance/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/app/', '/finance-settlement/') WHERE `selected` LIKE '/app/%';

UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/consumer/', '/content/') WHERE `selected` LIKE '/consumer/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/article/', '/content/') WHERE `selected` LIKE '/article/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/material/', '/content/') WHERE `selected` LIKE '/material/%';

UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/channel/', '/experience/') WHERE `selected` LIKE '/channel/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/decoration/', '/experience/') WHERE `selected` LIKE '/decoration/%';

UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/setting/', '/platform/') WHERE `selected` LIKE '/setting/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/organization/', '/org-auth/') WHERE `selected` LIKE '/organization/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/permission/', '/org-auth/') WHERE `selected` LIKE '/permission/%';
UPDATE `la_system_menu` SET `selected` = REPLACE(`selected`, '/staff_center/', '/staff-center/') WHERE `selected` LIKE '/staff_center/%';

-- staff 编辑页高亮修正
UPDATE `la_system_menu`
SET `selected` = '/ops/staff', `update_time` = UNIX_TIMESTAMP()
WHERE `perms` = 'staff.staff/add:edit';

-- ---------------------------------------------
-- 7. 路径 kebab-case 兜底（仅新分组二级菜单）
-- ---------------------------------------------
UPDATE `la_system_menu`
SET `paths` = REPLACE(`paths`, '_', '-'), `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND `pid` IN (@root_workbench_id, @root_ops_id, @root_growth_id, @root_finance_id, @root_content_id, @root_experience_id, @root_platform_id, @root_org_auth_id, @root_tools_id, @root_staff_center_id)
  AND INSTR(`paths`, '_') > 0;

-- ---------------------------------------------
-- 8. 旧一级菜单归档（隐藏，不删除）
-- ---------------------------------------------
UPDATE `la_system_menu`
SET `is_show` = 0, `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'M'
  AND `pid` = 0
  AND `paths` IN (
      'service','schedule','order','aftersale','review','dynamic','crm','financial','marketing','message','timeline',
      'consumer','article','material','channel','decoration','setting','organization','permission','template','dev_tools','app','finance','staff_center'
  );

SET FOREIGN_KEY_CHECKS = 1;
