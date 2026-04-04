-- =====================================================
-- 企微咨询与内部通知字段补充
-- 创建时间: 2026-03-16
-- 说明:
-- 1. 顾问表补充企微成员ID、对外联系二维码、联系链接
-- 2. 服务人员表补充企微成员ID
-- =====================================================

ALTER TABLE `la_sales_advisor`
    ADD COLUMN `wecom_userid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '企业微信成员ID' AFTER `wechat`,
    ADD COLUMN `contact_qr_code` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '联系我二维码' AFTER `wecom_userid`,
    ADD COLUMN `contact_link` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '联系我链接' AFTER `contact_qr_code`;

ALTER TABLE `la_sales_advisor`
    ADD KEY `idx_wecom_userid` (`wecom_userid`);

ALTER TABLE `la_staff`
    ADD COLUMN `wecom_userid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '企业微信成员ID' AFTER `mobile_full`;

ALTER TABLE `la_staff`
    ADD KEY `idx_wecom_userid` (`wecom_userid`);
