-- =============================================
-- 婚庆服务预约系统 - 管理后台菜单配置
-- 创建日期: 2026-01-20
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 婚庆服务管理一级菜单
-- ----------------------------

-- 服务管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '服务管理', 'el-icon-User', 900, '', 'service', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @service_menu_id = LAST_INSERT_ID();

-- 服务人员列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务人员', '', 100, 'staff.staff/lists', 'staff', 'staff/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 服务分类管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务分类', '', 90, 'service.service_category/lists', 'category', 'service/category/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 服务套餐管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@service_menu_id, 'C', '服务套餐', '', 80, 'service.service_package/lists', 'package', 'service/package/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 档期管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '档期管理', 'el-icon-Calendar', 850, '', 'schedule', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @schedule_menu_id = LAST_INSERT_ID();

-- 档期日历
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@schedule_menu_id, 'C', '档期日历', '', 100, 'schedule.schedule/lists', 'calendar', 'schedule/calendar/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 候补列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@schedule_menu_id, 'C', '候补列表', '', 80, 'schedule.waitlist/lists', 'waitlist', 'schedule/waitlist/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 平台级预约总览和吉日管理在当前版本不再作为后台菜单暴露。


-- 订单管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '订单管理', 'el-icon-Document', 800, '', 'order', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @order_menu_id = LAST_INSERT_ID();

-- 订单列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单列表', '', 100, 'order.order/lists', 'lists', 'order/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 退款管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '退款管理', '', 90, 'order.refund/lists', 'refund', 'order/refund/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 订单变更
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单变更', '', 80, 'order.order_change/lists', 'change', 'order/change/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 订单转让
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单转让', '', 70, 'order.order_transfer/lists', 'transfer', 'order/transfer/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 订单暂停
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@order_menu_id, 'C', '订单暂停', '', 60, 'order.order_pause/lists', 'pause', 'order/pause/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 动态社区（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '动态社区', 'el-icon-Picture', 750, '', 'dynamic', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @dynamic_menu_id = LAST_INSERT_ID();

-- 动态列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@dynamic_menu_id, 'C', '动态审核', '', 100, 'dynamic.dynamic/lists', 'lists', 'dynamic/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 评价管理（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '评价管理', 'el-icon-Star', 700, '', 'review', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @review_menu_id = LAST_INSERT_ID();

-- 评价列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@review_menu_id, 'C', '评价列表', '', 100, 'review.review/lists', 'lists', 'review/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 标签管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@review_menu_id, 'C', '标签管理', '', 90, 'review.review_tag/lists', 'tag', 'review/tag/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 申诉管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@review_menu_id, 'C', '申诉管理', '', 80, 'review.review_appeal/lists', 'appeal', 'review/appeal/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 敏感词管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@review_menu_id, 'C', '敏感词管理', '', 70, 'review.sensitive_word/lists', 'sensitive', 'review/sensitive/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 晒单奖励
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@review_menu_id, 'C', '晒单奖励', '', 60, 'growth.reviewShareReward/lists', 'share-reward', 'review/share_reward/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 财务中心（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '财务中心', 'el-icon-Coin', 650, '', 'financial', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @financial_menu_id = LAST_INSERT_ID();

-- 财务概览
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '财务概览', '', 100, 'financial.financial_report/overview', 'overview', 'financial/overview/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 资金流水
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '资金流水', '', 90, 'financial.flow/lists', 'flow', 'financial/flow/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 结算管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@financial_menu_id, 'C', '结算管理', '', 80, 'financial.settlement/lists', 'settlement', 'financial/settlement/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 精简版保留财务概览、资金流水、结算管理；
-- 成本管理、发票管理在当前版本不再作为后台菜单暴露。

-- CRM 管理、售后服务、时间线管理在精简版中不再作为后台菜单暴露。


-- 消息中心（一级菜单）
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (0, 'M', '消息中心', 'el-icon-Bell', 450, '', 'message', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @message_menu_id = LAST_INSERT_ID();

-- 消息通知
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@message_menu_id, 'C', '消息通知', '', 100, 'notification.notification/lists', 'notification', 'notification/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 订阅消息
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) 
VALUES (@message_menu_id, 'C', '订阅消息', '', 90, 'subscribe.subscribe/templateList', 'subscribe', 'subscribe/template/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


SET FOREIGN_KEY_CHECKS = 1;
