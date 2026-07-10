-- 订单结算按平台实收抵扣与补收

ALTER TABLE `la_staff_settlement`
    MODIFY COLUMN `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待结算,1=已结算,2=已取消,3=失败,4=转账处理中/待确认,5=无需打款',
    MODIFY COLUMN `settle_way` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '结算方式：1=余额,2=银行卡,3=微信,4=支付宝,5=无需打款',
    ADD COLUMN `platform_paid_share_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '本结算行分摊的平台实收金额' AFTER `platform_amount`,
    ADD COLUMN `staff_due_platform_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '服务人员应补平台金额' AFTER `platform_paid_share_amount`,
    ADD COLUMN `staff_due_collected_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '服务人员已补平台金额' AFTER `staff_due_platform_amount`,
    ADD COLUMN `staff_due_collect_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '补收状态：0=无需补收,1=待补收,2=部分补收,3=已补收' AFTER `staff_due_collected_amount`,
    ADD COLUMN `staff_due_collect_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最近补收时间' AFTER `staff_due_collect_status`,
    ADD COLUMN `staff_due_collect_admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最近补收管理员ID' AFTER `staff_due_collect_time`,
    ADD COLUMN `staff_due_collect_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '最近补收备注' AFTER `staff_due_collect_admin_id`,
    ADD KEY `idx_due_collect_status` (`staff_due_collect_status`);

CREATE TABLE IF NOT EXISTS `la_staff_settlement_repay` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `repay_sn` VARCHAR(32) NOT NULL COMMENT '补收编号',
    `settlement_id` INT UNSIGNED NOT NULL COMMENT '结算记录ID',
    `settlement_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '结算编号',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单项ID',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '补收金额',
    `collect_way` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '补收方式：1=微信支付,2=支付宝支付,3=后台线下补入',
    `pay_way` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '线上支付方式：0=无,2=微信,3=支付宝',
    `pay_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态：0=待支付,1=已支付,2=支付失败,3=已取消',
    `pay_sn` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方支付商户单号',
    `transaction_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '第三方交易号或线下补入编号',
    `callback_data` TEXT DEFAULT NULL COMMENT '支付回调数据',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '后台操作管理员ID',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付或补入时间',
    `callback_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回调时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_repay_sn` (`repay_sn`),
    KEY `idx_settlement_id` (`settlement_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_pay_sn` (`pay_sn`),
    KEY `idx_transaction_id` (`transaction_id`),
    KEY `idx_pay_status` (`pay_status`),
    KEY `idx_collect_way` (`collect_way`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员补交平台抽成记录表';

-- 历史待结算/失败/无需打款记录按平台实收净额重算；已结算、转账处理中、有转账明细的记录不自动反冲。
UPDATE `la_staff_settlement` ss
INNER JOIN (
    SELECT
        ss2.`id`,
        ROUND(
            IFNULL(op.`platform_paid_net_amount`, 0)
            * ss2.`order_amount`
            / NULLIF(ot.`total_staff_order_amount`, 0),
            2
        ) AS `paid_share`
    FROM `la_staff_settlement` ss2
    INNER JOIN (
        SELECT `order_id`, SUM(`order_amount`) AS `total_staff_order_amount`
        FROM `la_staff_settlement`
        GROUP BY `order_id`
    ) ot ON ot.`order_id` = ss2.`order_id`
    LEFT JOIN (
        SELECT
            `order_id`,
            SUM(GREATEST(`pay_amount` - IFNULL(`refund_amount`, 0), 0)) AS `platform_paid_net_amount`
        FROM `la_payment`
        WHERE `pay_way` <> 4
          AND `pay_status` IN (1, 2)
        GROUP BY `order_id`
    ) op ON op.`order_id` = ss2.`order_id`
) calc ON calc.`id` = ss.`id`
SET
    ss.`platform_paid_share_amount` = calc.`paid_share`,
    ss.`staff_due_platform_amount` = IF(calc.`paid_share` <= ss.`platform_amount`, ROUND(ss.`platform_amount` - calc.`paid_share`, 2), 0.00),
    ss.`staff_due_collected_amount` = 0.00,
    ss.`staff_due_collect_status` = IF(calc.`paid_share` <= ss.`platform_amount` AND ROUND(ss.`platform_amount` - calc.`paid_share`, 2) > 0, 1, 0),
    ss.`actual_amount` = IF(
        calc.`paid_share` > ss.`platform_amount`,
        LEAST(ss.`settlement_amount`, GREATEST(ROUND(calc.`paid_share` - ss.`platform_amount` - ss.`cost_amount`, 2), 0.00)),
        0.00
    ),
    ss.`status` = IF(
        calc.`paid_share` > ss.`platform_amount`
        AND LEAST(ss.`settlement_amount`, GREATEST(ROUND(calc.`paid_share` - ss.`platform_amount` - ss.`cost_amount`, 2), 0.00)) > 0,
        0,
        5
    ),
    ss.`settle_way` = IF(
        calc.`paid_share` > ss.`platform_amount`
        AND LEAST(ss.`settlement_amount`, GREATEST(ROUND(calc.`paid_share` - ss.`platform_amount` - ss.`cost_amount`, 2), 0.00)) > 0,
        3,
        5
    ),
    ss.`fail_reason` = '',
    ss.`remark` = LEFT(
        IF(
            ss.`remark` = '',
            '历史结算按平台实收抵扣平台抽成后重算',
            CONCAT(ss.`remark`, '，历史结算按平台实收抵扣平台抽成后重算')
        ),
        255
    ),
    ss.`update_time` = UNIX_TIMESTAMP()
WHERE ss.`status` IN (0, 3, 5)
  AND NOT EXISTS (
      SELECT 1
      FROM `la_staff_settlement_transfer` sst
      WHERE sst.`settlement_id` = ss.`id`
  );

-- 人工核对清单：这些历史记录已进入转账或结算链路，不自动反冲。
SELECT
    ss.`id`,
    ss.`settlement_sn`,
    ss.`order_id`,
    o.`order_sn`,
    ss.`staff_id`,
    ss.`status`,
    ss.`settle_way`,
    ss.`actual_amount`,
    ss.`platform_amount`,
    ss.`platform_paid_share_amount`,
    ss.`staff_due_platform_amount`
FROM `la_staff_settlement` ss
INNER JOIN `la_order` o ON o.`id` = ss.`order_id`
WHERE ss.`status` IN (1, 4)
   OR EXISTS (
      SELECT 1
      FROM `la_staff_settlement_transfer` sst
      WHERE sst.`settlement_id` = ss.`id`
  );
