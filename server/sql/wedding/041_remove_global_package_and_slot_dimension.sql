-- =====================================================
-- 去全局套餐并切换为按日期预约
-- 创建时间: 2026-03-17
-- 说明:
-- 1. service_package 全量收敛为 staff_id > 0 的人员专属套餐
-- 2. 折叠 staff_package 价格覆盖后，下线 staff_package
-- 3. 订单、候补、购物车、锁单全部按日期全天(time_slot = 0)归一
-- 4. schedule 按 staff_id + schedule_date 聚合为唯一全天记录
-- 5. 运行态派生数据按新口径清空或重建
-- =====================================================

-- =====================================================
-- 一、切换窗口前置要求（需由发布流程执行）
-- =====================================================
-- 1. 冻结套餐、下单、改期、候补、购物车写入。
-- 2. 确认没有新的支付回调、后台代客下单正在执行。
-- 3. 迁移完成前不要恢复写入。

SET @migration_time = UNIX_TIMESTAMP();

-- =====================================================
-- 二、构建套餐迁移映射
-- old_package_id + staff_id => new_package_id
-- =====================================================
DROP TEMPORARY TABLE IF EXISTS `tmp_package_mapping`;
CREATE TEMPORARY TABLE `tmp_package_mapping` (
    `old_package_id` INT UNSIGNED NOT NULL,
    `staff_id` INT UNSIGNED NOT NULL,
    `new_package_id` INT UNSIGNED DEFAULT NULL,
    `resolved_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `resolved_original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `migration_marker` VARCHAR(128) NOT NULL DEFAULT '',
    PRIMARY KEY (`old_package_id`, `staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 已经是人员专属且 staff_id 与 staff_package 一致的记录，直接折叠价格覆盖
INSERT INTO `tmp_package_mapping` (`old_package_id`, `staff_id`, `new_package_id`, `resolved_price`, `resolved_original_price`, `migration_marker`)
SELECT
    p.`id`,
    p.`staff_id`,
    p.`id`,
    COALESCE(sp.`custom_price`, sp.`price`, p.`price`, 0) AS resolved_price,
    COALESCE(sp.`original_price`, p.`original_price`, 0) AS resolved_original_price,
    CONCAT('KEEP#', p.`id`, '#', p.`staff_id`) AS migration_marker
FROM `la_service_package` p
INNER JOIN `la_staff_package` sp
    ON sp.`package_id` = p.`id`
   AND sp.`staff_id` = p.`staff_id`
WHERE p.`delete_time` IS NULL
  AND p.`staff_id` > 0
ON DUPLICATE KEY UPDATE
    `new_package_id` = VALUES(`new_package_id`),
    `resolved_price` = VALUES(`resolved_price`),
    `resolved_original_price` = VALUES(`resolved_original_price`);

-- 全局套餐按 old_package_id + staff_id 复制为人员专属套餐
INSERT INTO `tmp_package_mapping` (`old_package_id`, `staff_id`, `resolved_price`, `resolved_original_price`, `migration_marker`)
SELECT
    p.`id`,
    sp.`staff_id`,
    COALESCE(sp.`custom_price`, sp.`price`, p.`price`, 0) AS resolved_price,
    COALESCE(sp.`original_price`, p.`original_price`, 0) AS resolved_original_price,
    CONCAT('CLONE#', p.`id`, '#', sp.`staff_id`) AS migration_marker
FROM `la_service_package` p
INNER JOIN `la_staff_package` sp
    ON sp.`package_id` = p.`id`
WHERE p.`delete_time` IS NULL
  AND (p.`staff_id` = 0 OR p.`staff_id` IS NULL)
ON DUPLICATE KEY UPDATE
    `resolved_price` = VALUES(`resolved_price`),
    `resolved_original_price` = VALUES(`resolved_original_price`);

-- 折叠已经保留的专属套餐价格
UPDATE `la_service_package` p
INNER JOIN `tmp_package_mapping` m
    ON m.`new_package_id` = p.`id`
SET
    p.`price` = m.`resolved_price`,
    p.`original_price` = m.`resolved_original_price`,
    p.`update_time` = @migration_time
WHERE m.`new_package_id` IS NOT NULL
  AND p.`id` = m.`new_package_id`;

-- 复制原全局套餐为对应人员专属套餐，description 暂挂迁移标记，便于回填新 ID
INSERT INTO `la_service_package` (
    `category_id`,
    `staff_id`,
    `name`,
    `price`,
    `original_price`,
    `duration`,
    `description`,
    `image`,
    `sort`,
    `is_show`,
    `is_recommend`,
    `create_time`,
    `update_time`
)
SELECT
    p.`category_id`,
    m.`staff_id`,
    p.`name`,
    m.`resolved_price`,
    m.`resolved_original_price`,
    p.`duration`,
    CONCAT(COALESCE(p.`description`, ''), '\n[MIG:', m.`migration_marker`, ']'),
    p.`image`,
    p.`sort`,
    p.`is_show`,
    p.`is_recommend`,
    @migration_time,
    @migration_time
FROM `tmp_package_mapping` m
INNER JOIN `la_service_package` p
    ON p.`id` = m.`old_package_id`
WHERE m.`new_package_id` IS NULL;

-- 回填复制后产生的新套餐 ID
UPDATE `tmp_package_mapping` m
INNER JOIN `la_service_package` p
    ON p.`staff_id` = m.`staff_id`
   AND p.`delete_time` IS NULL
   AND p.`create_time` = @migration_time
   AND p.`description` LIKE CONCAT('%[MIG:', m.`migration_marker`, ']')
SET m.`new_package_id` = p.`id`
WHERE m.`new_package_id` IS NULL;

-- 清理迁移标记
UPDATE `la_service_package` p
INNER JOIN `tmp_package_mapping` m
    ON p.`id` = m.`new_package_id`
SET p.`description` = REPLACE(p.`description`, CONCAT('\n[MIG:', m.`migration_marker`, ']'), '')
WHERE p.`description` LIKE CONCAT('%[MIG:', m.`migration_marker`, ']');

-- 为原本已经是人员专属但未出现在 staff_package 的套餐补齐自映射
INSERT INTO `tmp_package_mapping` (`old_package_id`, `staff_id`, `new_package_id`, `resolved_price`, `resolved_original_price`, `migration_marker`)
SELECT
    p.`id`,
    p.`staff_id`,
    p.`id`,
    p.`price`,
    COALESCE(p.`original_price`, 0),
    CONCAT('SELF#', p.`id`, '#', p.`staff_id`)
FROM `la_service_package` p
WHERE p.`delete_time` IS NULL
  AND p.`staff_id` > 0
ON DUPLICATE KEY UPDATE
    `new_package_id` = VALUES(`new_package_id`);

-- =====================================================
-- 三、回写所有持久化 package_id
-- =====================================================
UPDATE `la_order_item` oi
INNER JOIN `tmp_package_mapping` m
    ON m.`old_package_id` = oi.`package_id`
   AND m.`staff_id` = oi.`staff_id`
SET oi.`package_id` = m.`new_package_id`
WHERE oi.`package_id` > 0
  AND m.`new_package_id` IS NOT NULL;

UPDATE `la_order_change` oc
INNER JOIN `tmp_package_mapping` m
    ON m.`old_package_id` = oc.`add_package_id`
   AND m.`staff_id` = oc.`add_staff_id`
SET oc.`add_package_id` = m.`new_package_id`
WHERE oc.`add_package_id` > 0
  AND m.`new_package_id` IS NOT NULL;

UPDATE `la_waitlist` w
INNER JOIN `tmp_package_mapping` m
    ON m.`old_package_id` = w.`package_id`
   AND m.`staff_id` = w.`staff_id`
SET w.`package_id` = m.`new_package_id`
WHERE w.`package_id` > 0
  AND m.`new_package_id` IS NOT NULL;

UPDATE `la_package_booking` pb
INNER JOIN `tmp_package_mapping` m
    ON m.`old_package_id` = pb.`package_id`
   AND m.`staff_id` = pb.`staff_id`
SET pb.`package_id` = m.`new_package_id`
WHERE pb.`package_id` > 0
  AND m.`new_package_id` IS NOT NULL;

UPDATE `la_cart` c
INNER JOIN `tmp_package_mapping` m
    ON m.`old_package_id` = c.`package_id`
   AND m.`staff_id` = c.`staff_id`
SET c.`package_id` = m.`new_package_id`
WHERE c.`package_id` > 0
  AND m.`new_package_id` IS NOT NULL;

-- =====================================================
-- 四、全库时间段归一为全天
-- =====================================================
UPDATE `la_order`
SET `service_time_slot` = 0
WHERE `service_time_slot` <> 0;

UPDATE `la_order_item`
SET `time_slot` = 0
WHERE `time_slot` <> 0;

UPDATE `la_order_change`
SET
    `old_time_slot` = 0,
    `new_time_slot` = 0,
    `add_time_slot` = 0
WHERE `old_time_slot` <> 0
   OR `new_time_slot` <> 0
   OR `add_time_slot` <> 0;

UPDATE `la_after_sale`
SET `expect_time_slot` = ''
WHERE `expect_time_slot` IS NOT NULL
  AND `expect_time_slot` <> '';

UPDATE `la_waitlist`
SET `time_slot` = 0
WHERE `time_slot` <> 0;

UPDATE `la_package_booking`
SET `time_slot` = 0
WHERE `time_slot` <> 0;

UPDATE `la_cart`
SET `time_slot` = 0
WHERE `time_slot` <> 0;

-- =====================================================
-- 五、聚合 schedule 为 staff_id + schedule_date 唯一全天记录
-- =====================================================
DROP TEMPORARY TABLE IF EXISTS `tmp_schedule_day`;
CREATE TEMPORARY TABLE `tmp_schedule_day` (
    `keep_id` INT UNSIGNED NOT NULL,
    `staff_id` INT UNSIGNED NOT NULL,
    `schedule_date` DATE NOT NULL,
    `merged_status` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `lock_type` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `lock_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `lock_expire_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `remark` VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`staff_id`, `schedule_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tmp_schedule_day` (`keep_id`, `staff_id`, `schedule_date`, `merged_status`, `order_id`, `lock_type`, `lock_user_id`, `lock_expire_time`, `remark`)
SELECT
    COALESCE(MAX(CASE WHEN s.`time_slot` = 0 THEN s.`id` END), MIN(s.`id`)) AS keep_id,
    s.`staff_id`,
    s.`schedule_date`,
    CASE
        WHEN SUM(CASE WHEN s.`status` = 2 THEN 1 ELSE 0 END) > 0 THEN 2
        WHEN SUM(CASE WHEN s.`status` = 4 THEN 1 ELSE 0 END) > 0 THEN 4
        WHEN SUM(CASE WHEN s.`status` = 3 THEN 1 ELSE 0 END) > 0 THEN 3
        WHEN SUM(CASE WHEN s.`status` = 1 THEN 1 ELSE 0 END) > 0 THEN 1
        ELSE 0
    END AS merged_status,
    COALESCE(MAX(CASE WHEN s.`status` = 2 THEN s.`order_id` END), 0) AS order_id,
    COALESCE(MAX(CASE WHEN s.`status` = 3 THEN s.`lock_type` END), 0) AS lock_type,
    COALESCE(MAX(CASE WHEN s.`status` = 3 THEN s.`lock_user_id` END), 0) AS lock_user_id,
    COALESCE(MAX(CASE WHEN s.`status` = 3 THEN s.`lock_expire_time` END), 0) AS lock_expire_time,
    COALESCE(MAX(NULLIF(s.`remark`, '')), '') AS remark
FROM `la_schedule` s
GROUP BY s.`staff_id`, s.`schedule_date`;

DROP TEMPORARY TABLE IF EXISTS `tmp_schedule_mapping`;
CREATE TEMPORARY TABLE `tmp_schedule_mapping` (
    `old_schedule_id` INT UNSIGNED NOT NULL,
    `new_schedule_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`old_schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tmp_schedule_mapping` (`old_schedule_id`, `new_schedule_id`)
SELECT s.`id`, d.`keep_id`
FROM `la_schedule` s
INNER JOIN `tmp_schedule_day` d
    ON d.`staff_id` = s.`staff_id`
   AND d.`schedule_date` = s.`schedule_date`;

DELETE s
FROM `la_schedule` s
INNER JOIN `tmp_schedule_day` d
    ON d.`staff_id` = s.`staff_id`
   AND d.`schedule_date` = s.`schedule_date`
WHERE s.`id` <> d.`keep_id`;

UPDATE `la_schedule` s
INNER JOIN `tmp_schedule_day` d
    ON d.`keep_id` = s.`id`
SET
    s.`time_slot` = 0,
    s.`status` = d.`merged_status`,
    s.`order_id` = d.`order_id`,
    s.`lock_type` = d.`lock_type`,
    s.`lock_user_id` = d.`lock_user_id`,
    s.`lock_expire_time` = d.`lock_expire_time`,
    s.`remark` = d.`remark`,
    s.`update_time` = @migration_time;

UPDATE `la_order_item` oi
INNER JOIN `tmp_schedule_mapping` sm
    ON sm.`old_schedule_id` = oi.`schedule_id`
SET oi.`schedule_id` = sm.`new_schedule_id`
WHERE oi.`schedule_id` > 0;

UPDATE `la_order_change` oc
LEFT JOIN `tmp_schedule_mapping` smo
    ON smo.`old_schedule_id` = oc.`old_schedule_id`
LEFT JOIN `tmp_schedule_mapping` smn
    ON smn.`old_schedule_id` = oc.`new_schedule_id`
LEFT JOIN `tmp_schedule_mapping` sma
    ON sma.`old_schedule_id` = oc.`add_schedule_id`
SET
    oc.`old_schedule_id` = COALESCE(smo.`new_schedule_id`, oc.`old_schedule_id`),
    oc.`new_schedule_id` = COALESCE(smn.`new_schedule_id`, oc.`new_schedule_id`),
    oc.`add_schedule_id` = COALESCE(sma.`new_schedule_id`, oc.`add_schedule_id`)
WHERE oc.`old_schedule_id` > 0
   OR oc.`new_schedule_id` > 0
   OR oc.`add_schedule_id` > 0;

-- =====================================================
-- 六、运行态派生数据清理/重建
-- =====================================================
DELETE FROM `la_package_booking`;

INSERT INTO `la_package_booking` (
    `package_id`,
    `staff_id`,
    `booking_date`,
    `time_slot`,
    `order_id`,
    `order_item_id`,
    `user_id`,
    `status`,
    `lock_expire_time`,
    `version`,
    `create_time`,
    `update_time`
)
SELECT
    oi.`package_id`,
    oi.`staff_id`,
    oi.`service_date`,
    0,
    oi.`order_id`,
    oi.`id`,
    o.`user_id`,
    2,
    NULL,
    1,
    @migration_time,
    @migration_time
FROM `la_order_item` oi
INNER JOIN `la_order` o
    ON o.`id` = oi.`order_id`
WHERE oi.`package_id` > 0
  AND o.`delete_time` IS NULL
  AND o.`order_status` NOT IN (6, 8);

DELETE FROM `la_schedule_lock`;
DELETE FROM `la_cart`;
DELETE FROM `la_cart_plan`;
DELETE FROM `la_waitlist`;

UPDATE `la_subscribe_message_scene`
SET
    `data_mapping` = '{"thing1":"staff_name","time2":"schedule_date","thing3":"package_name","thing4":"remark"}',
    `update_time` = @migration_time
WHERE `scene` = 'waitlist_release';

UPDATE `la_subscribe_message_template`
SET
    `content` = '{"thing1":{"key":"服务人员","value":""},"time2":{"key":"档期日期","value":""},"thing3":{"key":"套餐名称","value":""},"thing4":{"key":"备注","value":""}}',
    `keywords` = '服务人员,档期日期,套餐名称,备注',
    `update_time` = @migration_time
WHERE `scene` = 'waitlist_release';

-- =====================================================
-- 七、收缩结构
-- =====================================================
ALTER TABLE `la_service_package`
    MODIFY COLUMN `staff_id` INT UNSIGNED NOT NULL COMMENT '所属服务人员ID',
    DROP COLUMN `package_type`,
    DROP COLUMN `booking_type`,
    DROP COLUMN `allowed_time_slots`,
    DROP COLUMN `slot_prices`;

ALTER TABLE `la_service_package`
    DROP INDEX `idx_package_type`;

DROP TABLE IF EXISTS `la_staff_package`;

-- =====================================================
-- 八、校验建议（人工执行）
-- =====================================================
-- 1. SELECT COUNT(*) FROM la_service_package WHERE staff_id <= 0;
-- 2. SELECT COUNT(*) FROM la_order_item WHERE time_slot <> 0;
-- 3. SELECT COUNT(*) FROM la_order_change WHERE old_time_slot <> 0 OR new_time_slot <> 0 OR add_time_slot <> 0;
-- 4. SELECT COUNT(*) FROM la_schedule WHERE time_slot <> 0;
-- 5. SELECT COUNT(*) FROM la_package_booking WHERE time_slot <> 0;
