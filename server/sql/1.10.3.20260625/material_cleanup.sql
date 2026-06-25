-- Migration: 20260625_material_cleanup
-- Owner: backend
-- Scope: 后台素材清理、权限菜单与审计日志
-- Rollback: 确认无审计需要后可删除 la_file_cleanup_log，并删除菜单 520-523。

CREATE TABLE IF NOT EXISTS `la_file_cleanup_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
  `delete_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '成功清理数量',
  `registered_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '素材记录清理数量',
  `orphan_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '孤儿文件清理数量',
  `free_size` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '释放字节数',
  `failed_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '失败数量',
  `items` longtext NULL COMMENT '成功明细JSON',
  `failed_detail` longtext NULL COMMENT '失败明细JSON',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_time` (`admin_id`, `create_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='素材清理日志表';

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES
(520, 63, 'C', '素材清理', 'el-icon-Delete', 10, 'content.materialCleanup/summary', 'cleanup', 'material/cleanup', '', '', 0, 1, 0, 1779600000, 1779600000),
(521, 520, 'A', '扫描统计', '', 0, 'content.materialCleanup/summary', '', '', '', '', 0, 0, 0, 1779600000, 1779600000),
(522, 520, 'A', '候选列表', '', 0, 'content.materialCleanup/lists', '', '', '', '', 0, 0, 0, 1779600000, 1779600000),
(523, 520, 'A', '确认删除', '', 0, 'content.materialCleanup/delete', '', '', '', '', 0, 0, 0, 1779600000, 1779600000)
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

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 520
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 521
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 522
FROM `la_system_role`
WHERE `delete_time` IS NULL;

INSERT IGNORE INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT `id`, 523
FROM `la_system_role`
WHERE `delete_time` IS NULL;
