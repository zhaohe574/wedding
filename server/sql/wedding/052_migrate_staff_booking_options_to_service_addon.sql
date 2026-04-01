-- =====================================================
-- 052_migrate_staff_booking_options_to_service_addon.sql
-- 说明：
-- 1. 将 la_staff 旧的固定两组预约附加项迁移到 la_service_addon
-- 2. 不删除旧字段，仅完成数据搬迁，便于平滑切换与回滚核对
-- 3. 迁移脚本幂等，可重复执行
-- =====================================================

SET NAMES utf8mb4;

SET @now_ts = UNIX_TIMESTAMP();

-- 附加项1 -> service_addon
INSERT INTO `la_service_addon` (
    `staff_id`,
    `category_id`,
    `name`,
    `price`,
    `original_price`,
    `image`,
    `description`,
    `sort`,
    `is_show`,
    `create_time`,
    `update_time`
)
SELECT
    `s`.`id` AS `staff_id`,
    IFNULL(`s`.`category_id`, 0) AS `category_id`,
    TRIM(IFNULL(`s`.`booking_option_1_name`, '')) AS `name`,
    IFNULL(`s`.`booking_option_1_price`, 0.00) AS `price`,
    IFNULL(`s`.`booking_option_1_price`, 0.00) AS `original_price`,
    '' AS `image`,
    '' AS `description`,
    20 AS `sort`,
    1 AS `is_show`,
    @now_ts AS `create_time`,
    @now_ts AS `update_time`
FROM `la_staff` `s`
WHERE `s`.`delete_time` IS NULL
  AND TRIM(IFNULL(`s`.`booking_option_1_name`, '')) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM `la_service_addon` `a`
      WHERE `a`.`delete_time` IS NULL
        AND `a`.`staff_id` = `s`.`id`
        AND `a`.`name` = TRIM(IFNULL(`s`.`booking_option_1_name`, ''))
        AND `a`.`price` = IFNULL(`s`.`booking_option_1_price`, 0.00)
  );

-- 附加项2 -> service_addon
INSERT INTO `la_service_addon` (
    `staff_id`,
    `category_id`,
    `name`,
    `price`,
    `original_price`,
    `image`,
    `description`,
    `sort`,
    `is_show`,
    `create_time`,
    `update_time`
)
SELECT
    `s`.`id` AS `staff_id`,
    IFNULL(`s`.`category_id`, 0) AS `category_id`,
    TRIM(IFNULL(`s`.`booking_option_2_name`, '')) AS `name`,
    IFNULL(`s`.`booking_option_2_price`, 0.00) AS `price`,
    IFNULL(`s`.`booking_option_2_price`, 0.00) AS `original_price`,
    '' AS `image`,
    '' AS `description`,
    10 AS `sort`,
    1 AS `is_show`,
    @now_ts AS `create_time`,
    @now_ts AS `update_time`
FROM `la_staff` `s`
WHERE `s`.`delete_time` IS NULL
  AND TRIM(IFNULL(`s`.`booking_option_2_name`, '')) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM `la_service_addon` `a`
      WHERE `a`.`delete_time` IS NULL
        AND `a`.`staff_id` = `s`.`id`
        AND `a`.`name` = TRIM(IFNULL(`s`.`booking_option_2_name`, ''))
        AND `a`.`price` = IFNULL(`s`.`booking_option_2_price`, 0.00)
  );
