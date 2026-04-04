-- =====================================================
-- 附加服务套餐：独立数据结构与后台菜单
-- 创建时间：2026-03-17
-- 说明：
-- 1. 新增附加服务配置表 la_service_addon
-- 2. 新增订单附加服务快照表 la_order_item_addon
-- 3. 新增变更单附加服务明细表 la_order_change_addon
-- 4. 扩展订单与变更单字段
-- 5. 新增后台“附加服务管理”菜单与权限
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `la_service_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属服务人员ID',
    `category_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属服务分类ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '附加服务名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '售价',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
    `image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '图片',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '描述',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `is_show` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否上架：0=下架，1=上架',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `delete_time` INT UNSIGNED DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_is_show` (`is_show`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='附加服务配置表';

CREATE TABLE IF NOT EXISTS `la_order_item_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '主订单项ID',
    `addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
    `addon_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '附加服务快照名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '快照单价',
    `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量，固定为1',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1=生效中，2=已移除',
    `create_source` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '创建来源：1=下单，2=变更',
    `create_change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建变更单ID',
    `remove_change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '移除变更单ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_order_item_id` (`order_item_id`),
    KEY `idx_addon_id` (`addon_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单附加服务快照表';

CREATE TABLE IF NOT EXISTS `la_order_change_addon` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `change_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '变更单ID',
    `order_item_addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单附加服务快照ID',
    `addon_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '附加服务ID',
    `addon_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '附加服务名称',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
    `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_change_id` (`change_id`),
    KEY `idx_order_item_addon_id` (`order_item_addon_id`),
    KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单变更附加服务明细表';

SET @add_order_addon_amount_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order'
          AND COLUMN_NAME = 'addon_amount'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order` ADD COLUMN `addon_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT ''附加服务金额'' AFTER `total_amount`'
);

PREPARE stmt FROM @add_order_addon_amount_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `la_order_change`
    MODIFY COLUMN `change_type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '变更类型：1=改期，2=换人，3=加项，4=附加服务变更';

SET @add_order_change_addon_action_sql = IF(
    EXISTS(
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'la_order_change'
          AND COLUMN_NAME = 'addon_action'
    ),
    'SELECT 1',
    'ALTER TABLE `la_order_change` ADD COLUMN `addon_action` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''附加服务动作：1=新增，2=移除'' AFTER `change_type`'
);

PREPARE stmt FROM @add_order_change_addon_action_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- “附加服务管理”菜单
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'C', '附加服务', '', 75, 'ops.addon/lists', 'addon', 'service/addon/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `paths` = 'service' AND `type` = 'M'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/lists')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '详情', '', 0, 'ops.addon/detail', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/detail')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '新增', '', 0, 'ops.addon/add', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/add')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '编辑', '', 0, 'ops.addon/edit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/edit')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '删除', '', 0, 'ops.addon/delete', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/delete')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '状态切换', '', 0, 'ops.addon/changeStatus', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/changeStatus')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '全部', '', 0, 'ops.addon/all', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'ops.addon/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'ops.addon/all')
LIMIT 1;

-- 为已有“服务管理”菜单权限的角色自动授权附加服务菜单与动作权限
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT service_role.`role_id`, addon_menu.`id`
FROM `la_system_role_menu` service_role
JOIN `la_system_menu` service_root
  ON service_root.`id` = service_role.`menu_id`
 AND service_root.`type` = 'M'
 AND service_root.`paths` = 'service'
JOIN `la_system_menu` addon_menu
  ON addon_menu.`perms` = 'ops.addon/lists'
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_system_role_menu` exists_map
    WHERE exists_map.`role_id` = service_role.`role_id`
      AND exists_map.`menu_id` = addon_menu.`id`
);

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT service_role.`role_id`, addon_action.`id`
FROM `la_system_role_menu` service_role
JOIN `la_system_menu` service_root
  ON service_root.`id` = service_role.`menu_id`
 AND service_root.`type` = 'M'
 AND service_root.`paths` = 'service'
JOIN `la_system_menu` addon_menu
  ON addon_menu.`perms` = 'ops.addon/lists'
JOIN `la_system_menu` addon_action
  ON addon_action.`pid` = addon_menu.`id`
 AND addon_action.`type` = 'A'
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_system_role_menu` exists_map
    WHERE exists_map.`role_id` = service_role.`role_id`
      AND exists_map.`menu_id` = addon_action.`id`
);

SET FOREIGN_KEY_CHECKS = 1;
