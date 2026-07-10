-- Migration: 20260623_monthly_report
-- Owner: backend
-- Scope: 后台单量月报素材、模板、确认记录与菜单权限
-- Rollback: 若确认无月报数据，可删除新增菜单权限和三张 monthly_report 表。

CREATE TABLE IF NOT EXISTS `la_monthly_report_material` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '绑定服务人员ID',
  `photo` varchar(500) NOT NULL DEFAULT '' COMMENT '兼容旧月报照片',
  `english_name` varchar(80) NOT NULL DEFAULT '' COMMENT '英文名或拼音',
  `chinese_name` varchar(80) NOT NULL DEFAULT '' COMMENT '中文名',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=停用,1=启用',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_staff` (`staff_id`),
  KEY `idx_status_sort` (`status`,`sort`,`id`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='单量月报素材表';

SET @avatar_photo_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'la_monthly_report_material'
    AND COLUMN_NAME = 'avatar_photo'
);
SET @sql := IF(
  @avatar_photo_exists = 0,
  'ALTER TABLE `la_monthly_report_material` ADD COLUMN `avatar_photo` varchar(500) NOT NULL DEFAULT '''' COMMENT ''头像素材：执行榜使用'' AFTER `photo`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @half_body_photo_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'la_monthly_report_material'
    AND COLUMN_NAME = 'half_body_photo'
);
SET @sql := IF(
  @half_body_photo_exists = 0,
  'ALTER TABLE `la_monthly_report_material` ADD COLUMN `half_body_photo` varchar(500) NOT NULL DEFAULT '''' COMMENT ''半身素材：单王使用'' AFTER `avatar_photo`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `la_monthly_report_material`
SET
  `avatar_photo` = IF(`avatar_photo` = '', `photo`, `avatar_photo`),
  `half_body_photo` = IF(`half_body_photo` = '', `photo`, `half_body_photo`)
WHERE `photo` <> '';

CREATE TABLE IF NOT EXISTS `la_monthly_report_template` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `template_type` varchar(20) NOT NULL DEFAULT 'addition' COMMENT '模板类型：addition=新增档期,ranking=执行榜,top=单王',
  `template_name` varchar(80) NOT NULL DEFAULT '' COMMENT '模板名称',
  `template_version` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '模板版本号',
  `is_default` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认模板：0=否,1=是',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '模板状态：0=停用,1=启用',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序值',
  `design_version` varchar(60) NOT NULL DEFAULT 'monthly-report-designer-v1' COMMENT '设计器版本',
  `design_config` json NULL COMMENT '自由设计配置',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_type_status_default` (`template_type`,`status`,`is_default`),
  KEY `idx_type_version` (`template_type`,`template_version`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='单量月报模板表';

CREATE TABLE IF NOT EXISTS `la_monthly_report` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `report_month` char(7) NOT NULL DEFAULT '' COMMENT '月报月份：YYYY-MM',
  `version` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '月份内版本号',
  `is_current` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否当前版本：0=否,1=是',
  `category_ids` json NULL COMMENT '统计服务分类范围',
  `addition_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最终新增档期数',
  `executed_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最终共执行场次',
  `top_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最终单王场次',
  `auto_snapshot` json NULL COMMENT '自动统计快照',
  `final_snapshot` json NULL COMMENT '运营确认快照',
  `template_snapshot` json NULL COMMENT '模板版本快照',
  `rendered_snapshot` json NULL COMMENT '渲染快照',
  `snapshot_hash` char(64) NOT NULL DEFAULT '' COMMENT '快照哈希',
  `issues` json NULL COMMENT '确认时问题清单',
  `addition_image_url` varchar(500) NOT NULL DEFAULT '' COMMENT '新增档期图片',
  `ranking_image_url` varchar(500) NOT NULL DEFAULT '' COMMENT '执行榜图片',
  `top_image_url` varchar(500) NOT NULL DEFAULT '' COMMENT '单王图片',
  `confirm_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '确认管理员ID',
  `confirm_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '确认时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_month_version` (`report_month`,`version`),
  KEY `idx_month_current` (`report_month`,`is_current`),
  KEY `idx_current_month` (`is_current`,`report_month`),
  KEY `idx_snapshot` (`snapshot_hash`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='单量月报确认记录表';

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES
(479, 187, 'C', '单量月报', '', 70, 'ops.monthlyReport/preview', 'monthly-report', 'order/monthly_report/index', '', '', 0, 1, 0, 1778000000, 1778000000),
(480, 479, 'A', '素材列表', '', 0, 'ops.monthlyReport/materialLists', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(481, 479, 'A', '保存素材', '', 0, 'ops.monthlyReport/materialSave', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(482, 479, 'A', '删除素材', '', 0, 'ops.monthlyReport/materialDelete', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(483, 479, 'A', '人员选项', '', 0, 'ops.monthlyReport/staffOptions', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(484, 479, 'A', '分类选项', '', 0, 'ops.monthlyReport/categoryOptions', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(485, 479, 'A', '模板配置', '', 0, 'ops.monthlyReport/templateConfig', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(486, 479, 'A', '保存模板', '', 0, 'ops.monthlyReport/templateSave', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(487, 479, 'A', '复制模板', '', 0, 'ops.monthlyReport/templateCopy', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(488, 479, 'A', '设置默认模板', '', 0, 'ops.monthlyReport/templateSetDefault', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(489, 479, 'A', '停用模板', '', 0, 'ops.monthlyReport/templateDisable', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(490, 479, 'A', '模板预览', '', 0, 'ops.monthlyReport/templatePreview', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(491, 479, 'A', '生成预览', '', 0, 'ops.monthlyReport/preview', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(492, 479, 'A', '确认月报', '', 0, 'ops.monthlyReport/confirm', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(493, 479, 'A', '最新版本', '', 0, 'ops.monthlyReport/latest', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(494, 479, 'A', '历史版本', '', 0, 'ops.monthlyReport/history', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(495, 479, 'A', '版本详情', '', 0, 'ops.monthlyReport/detail', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(496, 479, 'A', '重建图片', '', 0, 'ops.monthlyReport/regenerateAssets', '', '', '', '', 0, 0, 0, 1778000000, 1778000000),
(497, 0, 'M', '快捷工具', 'el-icon-Tools', 690, '', 'quick-tools', '', '', '', 0, 1, 0, 1778000100, 1778000100),
(498, 497, 'A', '快捷工具代理', '', 0, 'ops.quickTool/page', '', '', '', '', 0, 0, 0, 1778000100, 1778000100),
(499, 497, 'A', '快捷工具票据', '', 0, 'ops.quickTool/ticket', '', '', '', '', 0, 0, 0, 1778000100, 1778000100),
(500, 497, 'A', '快捷工具接口代理', '', 0, 'ops.quickTool/api', '', '', '', '', 0, 0, 0, 1778000100, 1778000100),
(501, 497, 'C', '人像分割', '', 30, 'ops.quickTool/ticket', 'human-split', 'tools/quick/human_split', '', '', 0, 1, 0, 1778000100, 1778000100),
(502, 497, 'C', '图像压缩', '', 20, 'ops.quickTool/ticket', 'compress', 'tools/quick/compress', '', '', 0, 1, 0, 1778000100, 1778000100),
(503, 497, 'C', '图片格式转换', '', 10, 'ops.quickTool/ticket', 'convert', 'tools/quick/convert', '', '', 0, 1, 0, 1778000100, 1778000100),
(504, 497, 'C', '图片编辑', '', 40, 'ops.quickTool/ticket', 'image-edit', 'tools/quick/image_edit', '', '', 0, 1, 0, 1778000100, 1778000100),
(505, 497, 'C', '视频压缩', '', 50, '', 'video-compress', 'tools/quick/video_compress', '', '', 0, 1, 0, 1778199189, 1778199189)
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`pid` = VALUES(`pid`),
`type` = VALUES(`type`),
`icon` = VALUES(`icon`),
`perms` = VALUES(`perms`),
`paths` = VALUES(`paths`),
`component` = VALUES(`component`),
`sort` = VALUES(`sort`),
`selected` = VALUES(`selected`),
`params` = VALUES(`params`),
`is_cache` = VALUES(`is_cache`),
`is_show` = VALUES(`is_show`),
`is_disable` = VALUES(`is_disable`),
`update_time` = VALUES(`update_time`);

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`) VALUES
(2, 479),
(2, 480),
(2, 481),
(2, 482),
(2, 483),
(2, 484),
(2, 485),
(2, 486),
(2, 487),
(2, 488),
(2, 489),
(2, 490),
(2, 491),
(2, 492),
(2, 493),
(2, 494),
(2, 495),
(2, 496),
(2, 505);

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 497
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 498
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 499
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 500
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 501
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 502
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 503
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 504
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 505
FROM `la_system_role`
WHERE `delete_time` IS NULL;
