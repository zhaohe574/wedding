-- =============================================
-- 优惠券模块清理脚本
-- 创建日期：2026-03-17
-- 说明：
-- 1. 删除优惠券业务表与订单优惠券关联字段
-- 2. 清理后台菜单、角色菜单关联与旧权限标识
-- 3. 适用于已落地优惠券模块的历史库清理
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 删除订单优惠券字段
-- ----------------------------
SET @drop_order_user_coupon_id = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'la_order'
              AND COLUMN_NAME = 'user_coupon_id'
        ),
        'ALTER TABLE `la_order` DROP COLUMN `user_coupon_id`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @drop_order_user_coupon_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_order_coupon_id = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'la_order'
              AND COLUMN_NAME = 'coupon_id'
        ),
        'ALTER TABLE `la_order` DROP COLUMN `coupon_id`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @drop_order_coupon_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @drop_order_coupon_amount = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'la_order'
              AND COLUMN_NAME = 'coupon_amount'
        ),
        'ALTER TABLE `la_order` DROP COLUMN `coupon_amount`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @drop_order_coupon_amount;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 删除优惠券表
-- ----------------------------
DROP TABLE IF EXISTS `la_user_coupon`;
DROP TABLE IF EXISTS `la_coupon`;

-- ----------------------------
-- 清理优惠券菜单与角色关联
-- ----------------------------
CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_coupon_menu_ids` (
    `id` INT UNSIGNED NOT NULL PRIMARY KEY
);

TRUNCATE TABLE `tmp_coupon_menu_ids`;

INSERT IGNORE INTO `tmp_coupon_menu_ids` (`id`)
SELECT `id`
FROM `la_system_menu`
WHERE `paths` = 'marketing'
   OR `component` LIKE 'coupon/%'
   OR `perms` LIKE 'coupon.coupon/%'
   OR `perms` LIKE 'growth.campaign/%';

DELETE rm
FROM `la_system_role_menu` rm
INNER JOIN `tmp_coupon_menu_ids` t ON t.`id` = rm.`menu_id`;

DELETE m
FROM `la_system_menu` m
INNER JOIN `tmp_coupon_menu_ids` t ON t.`id` = m.`id`;

DROP TEMPORARY TABLE IF EXISTS `tmp_coupon_menu_ids`;

SET FOREIGN_KEY_CHECKS = 1;
