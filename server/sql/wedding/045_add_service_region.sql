-- =====================================================
-- 地区服务能力
-- 创建时间：2026-03-18
-- 说明：
-- 1. 新增可接单城市池表 la_service_city_pool
-- 2. 新增套餐地区价格表 la_service_package_region_price
-- 3. 扩展订单表省/市/区县字段
-- 4. 新增后台“服务地区”菜单与权限
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `la_service_city_pool` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `province_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '省编码',
    `province_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '省名称',
    `city_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '市编码',
    `city_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '市名称',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=停用，1=启用',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_city_code` (`city_code`),
    KEY `idx_status_sort` (`status`, `sort`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='可接单城市池';

CREATE TABLE IF NOT EXISTS `la_service_package_region_price` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `package_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '套餐ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '人员ID',
    `region_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '地区层级：2=市，3=区县',
    `province_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '省编码',
    `province_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '省名称',
    `city_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '市编码',
    `city_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '市名称',
    `district_code` VARCHAR(12) NOT NULL DEFAULT '' COMMENT '区县编码',
    `district_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '区县名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '地区售价',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_package_region` (`package_id`, `region_level`, `city_code`, `district_code`),
    KEY `idx_staff_package` (`staff_id`, `package_id`),
    KEY `idx_city_district` (`city_code`, `district_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='套餐地区价格表';

SET @add_order_service_province_code_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_province_code'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_province_code` VARCHAR(12) NOT NULL DEFAULT '''' COMMENT ''服务省编码'' AFTER `service_address`'
);
PREPARE stmt FROM @add_order_service_province_code_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_order_service_province_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_province'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_province` VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''服务省'' AFTER `service_province_code`'
);
PREPARE stmt FROM @add_order_service_province_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_order_service_city_code_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_city_code'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_city_code` VARCHAR(12) NOT NULL DEFAULT '''' COMMENT ''服务市编码'' AFTER `service_province`'
);
PREPARE stmt FROM @add_order_service_city_code_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_order_service_city_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_city'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_city` VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''服务市'' AFTER `service_city_code`'
);
PREPARE stmt FROM @add_order_service_city_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_order_service_district_code_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_district_code'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_district_code` VARCHAR(12) NOT NULL DEFAULT '''' COMMENT ''服务区县编码'' AFTER `service_city`'
);
PREPARE stmt FROM @add_order_service_district_code_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_order_service_district_sql = IF(
    EXISTS(
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'service_district'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `service_district` VARCHAR(50) NOT NULL DEFAULT '''' COMMENT ''服务区县'' AFTER `service_district_code`'
);
PREPARE stmt FROM @add_order_service_district_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '服务地区', '', 74, 'ops.region/lists', 'region', 'service/region/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/lists')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '城市选项', '', 0, 'ops.region/cityOptions', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/cityOptions')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '启用城市选项', '', 0, 'ops.region/enabledCityOptions', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/enabledCityOptions')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '区县选项', '', 0, 'ops.region/districtOptions', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/districtOptions')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '新增', '', 0, 'ops.region/add', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/add')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '编辑', '', 0, 'ops.region/edit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/edit')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '删除', '', 0, 'ops.region/delete', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/delete')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '状态切换', '', 0, 'ops.region/changeStatus', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.region/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.region/changeStatus')
LIMIT 1;

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT service_role.`role_id`, region_menu.`id`
FROM `la_system_role_menu` service_role
JOIN `la_system_menu` service_root
  ON service_root.`id` = service_role.`menu_id`
 AND service_root.`type` = 'M'
 AND service_root.`paths` = 'service'
JOIN `la_system_menu` region_menu
  ON region_menu.`perms` = 'ops.region/lists'
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_system_role_menu` exists_map
    WHERE exists_map.`role_id` = service_role.`role_id`
      AND exists_map.`menu_id` = region_menu.`id`
);

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT service_role.`role_id`, region_action.`id`
FROM `la_system_role_menu` service_role
JOIN `la_system_menu` service_root
  ON service_root.`id` = service_role.`menu_id`
 AND service_root.`type` = 'M'
 AND service_root.`paths` = 'service'
JOIN `la_system_menu` region_menu
  ON region_menu.`perms` = 'ops.region/lists'
JOIN `la_system_menu` region_action
  ON region_action.`pid` = region_menu.`id`
 AND region_action.`type` = 'A'
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_system_role_menu` exists_map
    WHERE exists_map.`role_id` = service_role.`role_id`
      AND exists_map.`menu_id` = region_action.`id`
);

SET FOREIGN_KEY_CHECKS = 1;
