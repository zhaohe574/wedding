-- Migration: 20260618_staff_schedule_confirm_letter
-- Owner: backend
-- Scope: 服务人员档期确认函个人配置与生成记录
-- Rollback: 若确认无新确认函数据，可删除新增菜单权限和两张 staff_schedule_confirm_letter 表。

CREATE TABLE IF NOT EXISTS `la_staff_schedule_confirm_letter_config` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
  `title` varchar(80) NOT NULL DEFAULT '档期已定' COMMENT '标题',
  `subtitle` varchar(120) NOT NULL DEFAULT 'SCHEDULE RESERVED' COMMENT '副标题',
  `content_template` varchar(500) NOT NULL DEFAULT '{service_date_label}，{customer_alias}的{service_name}档期已确认，感谢信任。' COMMENT '正文模板',
  `footer_note` varchar(200) NOT NULL DEFAULT '档期已预定，感谢信任与选择。' COMMENT '页脚文案',
  `background_type` varchar(20) NOT NULL DEFAULT 'color' COMMENT '背景类型：color=纯色,image=图片',
  `background_image` varchar(500) NOT NULL DEFAULT '' COMMENT '背景图',
  `background_color` varchar(20) NOT NULL DEFAULT '#191713' COMMENT '背景色',
  `text_theme` varchar(20) NOT NULL DEFAULT 'light' COMMENT '文字主题：light=浅色,dark=深色',
  `show_customer_alias` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否展示脱敏客户称呼',
  `show_service_name` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否展示服务类型',
  `show_city` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否展示城市',
  `show_qrcode` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否展示二维码',
  `qrcode_image` varchar(500) NOT NULL DEFAULT '' COMMENT '二维码图片',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员档期确认函配置表';

CREATE TABLE IF NOT EXISTS `la_staff_schedule_confirm_letter` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
  `version` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '版本号',
  `is_outdated` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否失效：0=否,1=是',
  `rendered_snapshot` json NOT NULL COMMENT '渲染快照JSON',
  `render_spec_version` varchar(60) NOT NULL DEFAULT 'staff-schedule-designer-v2' COMMENT '渲染版本',
  `snapshot_hash` char(64) NOT NULL DEFAULT '' COMMENT '快照哈希',
  `full_image_url` varchar(500) NOT NULL DEFAULT '' COMMENT '海报全图缓存',
  `thumb_image_url` varchar(500) NOT NULL DEFAULT '' COMMENT '海报缩略图缓存',
  `confirm_date` date NOT NULL COMMENT '确认日期',
  `generate_source` varchar(20) NOT NULL DEFAULT 'staff' COMMENT '生成来源：staff=服务人员,admin=后台',
  `generate_staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '生成服务人员ID',
  `generate_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '生成管理员ID',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_staff_version` (`order_id`,`staff_id`,`version`),
  KEY `idx_order_staff_current` (`order_id`,`staff_id`,`is_outdated`),
  KEY `idx_staff_time` (`staff_id`,`create_time`),
  KEY `idx_snapshot` (`snapshot_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员档期确认函版本表';

ALTER TABLE `la_staff_schedule_confirm_letter_config`
  DROP INDEX `uk_staff_id`,
  ADD COLUMN `template_name` varchar(80) NOT NULL DEFAULT '默认海报' COMMENT '模板名称' AFTER `staff_id`,
  ADD COLUMN `template_version` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '模板版本号' AFTER `template_name`,
  ADD COLUMN `is_default` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否默认模板：0=否,1=是' AFTER `template_version`,
  ADD COLUMN `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '模板状态：0=停用,1=启用' AFTER `is_default`,
  ADD COLUMN `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值' AFTER `status`,
  ADD COLUMN `design_version` varchar(60) NOT NULL DEFAULT 'staff-schedule-designer-v2' COMMENT '海报设计版本' AFTER `qrcode_image`,
  ADD COLUMN `design_config` json NULL COMMENT '自由设计配置' AFTER `design_version`,
  ADD KEY `idx_staff_status_default` (`staff_id`,`status`,`is_default`),
  ADD KEY `idx_staff_sort` (`staff_id`,`sort`,`id`);

UPDATE `la_staff_schedule_confirm_letter_config`
SET `template_name` = IF(`template_name` = '', '默认海报', `template_name`),
    `template_version` = IF(`template_version` = 0, 1, `template_version`),
    `is_default` = 1,
    `status` = 1,
    `design_version` = 'staff-schedule-designer-v2',
    `design_config` = NULL;

ALTER TABLE `la_staff_schedule_confirm_letter`
  ADD COLUMN `config_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '生成使用的模板配置ID' AFTER `staff_id`,
  ADD COLUMN `config_name` varchar(80) NOT NULL DEFAULT '历史配置' COMMENT '生成使用的模板名称' AFTER `config_id`,
  ADD COLUMN `config_template_version` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '生成使用的模板版本号' AFTER `config_name`,
  ADD KEY `idx_config` (`config_id`),
  MODIFY COLUMN `render_spec_version` varchar(60) NOT NULL DEFAULT 'staff-schedule-designer-v2' COMMENT '渲染版本';

UPDATE `la_config`
SET `value` = ''
WHERE `type` = 'order_confirmation_letter' AND `name` = 'remark_template';

UPDATE `la_system_menu`
SET `name` = '确认函字体资源', `perms` = 'setting.order_confirm_letter/fontLists'
WHERE `id` = 327;

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES
(465, 223, 'C', '档期确认函', '', 36, 'ops.staff/myScheduleConfirmLetterConfig', 'schedule-confirm-letter', 'staff_center/schedule_confirm_letter/index', '', '', 0, 1, 0, 1777800000, 1777800000),
(466, 465, 'A', '保存档期确认函', '', 0, 'ops.staff/myScheduleConfirmLetterSave', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(467, 465, 'A', '预览档期确认函', '', 0, 'ops.staff/myScheduleConfirmLetterPreview', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(468, 215, 'A', '读取人员档期确认函', '', 0, 'ops.staff/scheduleConfirmLetterConfig', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(469, 215, 'A', '保存人员档期确认函', '', 0, 'ops.staff/scheduleConfirmLetterSave', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(470, 215, 'A', '预览人员档期确认函', '', 0, 'ops.staff/scheduleConfirmLetterPreview', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(471, 465, 'A', '复制档期确认函模板', '', 0, 'ops.staff/myScheduleConfirmLetterCopy', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(472, 465, 'A', '设置默认档期确认函模板', '', 0, 'ops.staff/myScheduleConfirmLetterSetDefault', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(473, 465, 'A', '停用档期确认函模板', '', 0, 'ops.staff/myScheduleConfirmLetterDisable', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(474, 215, 'A', '复制人员档期确认函模板', '', 0, 'ops.staff/scheduleConfirmLetterCopy', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(475, 215, 'A', '设置人员默认档期确认函模板', '', 0, 'ops.staff/scheduleConfirmLetterSetDefault', '', '', '', '', 0, 0, 0, 1777800000, 1777800000),
(476, 215, 'A', '停用人员档期确认函模板', '', 0, 'ops.staff/scheduleConfirmLetterDisable', '', '', '', '', 0, 0, 0, 1777800000, 1777800000)
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`perms` = VALUES(`perms`),
`paths` = VALUES(`paths`),
`component` = VALUES(`component`),
`sort` = VALUES(`sort`),
`is_show` = VALUES(`is_show`),
`update_time` = VALUES(`update_time`);

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`) VALUES
(1, 465),
(1, 466),
(1, 467),
(1, 471),
(1, 472),
(1, 473),
(2, 465),
(2, 466),
(2, 467),
(2, 468),
(2, 469),
(2, 470),
(2, 471),
(2, 472),
(2, 473),
(2, 474),
(2, 475),
(2, 476);
