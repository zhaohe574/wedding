-- =============================================
-- 后台权限标识迁移脚本（覆盖域）
-- 文件名：perms_migration.sql
-- 说明：
-- 1) 仅迁移本次重构覆盖域 perms
-- 2) 必须与后端路由、前端 API 与 v-perms 同版本原子上线
-- 3) 非覆盖域（auth/dept/setting/tools）不改前缀
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- 1. 履约运营域：ops.*/*
-- ---------------------------------------------
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'staff.staff/', 'ops.staff/') WHERE `perms` LIKE 'staff.staff/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'staff.work/', 'ops.work/') WHERE `perms` LIKE 'staff.work/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'service.service_category/', 'ops.serviceCategory/') WHERE `perms` LIKE 'service.service_category/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'service.service_package/', 'ops.servicePackage/') WHERE `perms` LIKE 'service.service_package/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'service.styleTag/', 'ops.styleTag/') WHERE `perms` LIKE 'service.styleTag/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'service.style_tag/', 'ops.styleTag/') WHERE `perms` LIKE 'service.style_tag/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'schedule.schedule/', 'ops.schedule/') WHERE `perms` LIKE 'schedule.schedule/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'schedule.scheduleRule/', 'ops.scheduleRule/') WHERE `perms` LIKE 'schedule.scheduleRule/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'schedule.booking/', 'ops.booking/') WHERE `perms` LIKE 'schedule.booking/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'schedule.waitlist/', 'ops.waitlist/') WHERE `perms` LIKE 'schedule.waitlist/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'schedule.calendarEvent/', 'ops.calendarEvent/') WHERE `perms` LIKE 'schedule.calendarEvent/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'order.order/', 'ops.order/') WHERE `perms` LIKE 'order.order/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'order.order_change/', 'ops.orderChange/') WHERE `perms` LIKE 'order.order_change/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'order.order_transfer/', 'ops.orderTransfer/') WHERE `perms` LIKE 'order.order_transfer/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'order.order_pause/', 'ops.orderPause/') WHERE `perms` LIKE 'order.order_pause/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'order.refund/', 'ops.refund/') WHERE `perms` LIKE 'order.refund/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'aftersale.aftersale/', 'ops.aftersaleTicket/') WHERE `perms` LIKE 'aftersale.aftersale/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'aftersale.complaint/', 'ops.complaint/') WHERE `perms` LIKE 'aftersale.complaint/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'aftersale.reshoot/', 'ops.reshoot/') WHERE `perms` LIKE 'aftersale.reshoot/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'aftersale.callback/', 'ops.callback/') WHERE `perms` LIKE 'aftersale.callback/%';

-- ---------------------------------------------
-- 2. 客户增长域：growth.*/*
-- ---------------------------------------------
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'crm.customer/', 'growth.customer/') WHERE `perms` LIKE 'crm.customer/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'crm.sales_advisor/', 'growth.advisor/') WHERE `perms` LIKE 'crm.sales_advisor/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'crm.customer_loss_warning/', 'growth.lossWarning/') WHERE `perms` LIKE 'crm.customer_loss_warning/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'crm.followRecord/', 'growth.followRecord/') WHERE `perms` LIKE 'crm.followRecord/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'dynamic.dynamic/', 'growth.dynamic/') WHERE `perms` LIKE 'dynamic.dynamic/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'dynamic.dynamicComment/', 'growth.dynamicComment/') WHERE `perms` LIKE 'dynamic.dynamicComment/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'review.review/', 'growth.review/') WHERE `perms` LIKE 'review.review/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'review.reviewAppeal/', 'growth.reviewAppeal/') WHERE `perms` LIKE 'review.reviewAppeal/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'review.reviewTag/', 'growth.reviewTag/') WHERE `perms` LIKE 'review.reviewTag/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'review.sensitiveWord/', 'growth.sensitiveWord/') WHERE `perms` LIKE 'review.sensitiveWord/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'review.review_appeal/', 'growth.reviewAppeal/') WHERE `perms` LIKE 'review.review_appeal/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'review.review_tag/', 'growth.reviewTag/') WHERE `perms` LIKE 'review.review_tag/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'review.sensitive_word/', 'growth.sensitiveWord/') WHERE `perms` LIKE 'review.sensitive_word/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'notification.notification/', 'growth.notification/') WHERE `perms` LIKE 'notification.notification/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'subscribe.subscribe/', 'growth.subscribe/') WHERE `perms` LIKE 'subscribe.subscribe/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'timeline.timeline/', 'growth.timeline/') WHERE `perms` LIKE 'timeline.timeline/%';

-- ---------------------------------------------
-- 3. 财务结算域：finance.*/*
-- ---------------------------------------------
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'financial.', 'finance.') WHERE `perms` LIKE 'financial.%/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'finance.account_log/', 'finance.accountLog/') WHERE `perms` LIKE 'finance.account_log/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'recharge.recharge/', 'finance.recharge/') WHERE `perms` LIKE 'recharge.recharge/%';

-- ---------------------------------------------
-- 4. 用户与内容域：content.*/*
-- ---------------------------------------------
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'user.user/', 'content.user/') WHERE `perms` LIKE 'user.user/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'article.article/', 'content.article/') WHERE `perms` LIKE 'article.article/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'article.article_cate/', 'content.articleCategory/') WHERE `perms` LIKE 'article.article_cate/%';
UPDATE `la_system_menu` SET `perms` = 'content.material/listCate' WHERE `perms` = 'file/listCate';

-- ---------------------------------------------
-- 5. 渠道与装修域：experience.*/*
-- ---------------------------------------------
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'channel.', 'experience.channel.') WHERE `perms` LIKE 'channel.%/%';
UPDATE `la_system_menu` SET `perms` = REPLACE(`perms`, 'decorate.', 'experience.decorate.') WHERE `perms` LIKE 'decorate.%/%';

SET FOREIGN_KEY_CHECKS = 1;
