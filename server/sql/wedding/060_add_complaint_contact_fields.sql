-- ============================================================
-- 060_add_complaint_contact_fields.sql
-- 投诉记录补充联系方式字段
-- 1. 新增联系人
-- 1. 新增联系手机号
-- 2. 新增联系手机号
-- ============================================================

ALTER TABLE `la_complaint`
    ADD COLUMN `contact_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '联系人' AFTER `expect_result`,
    ADD COLUMN `contact_mobile` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '联系手机号' AFTER `contact_name`;
