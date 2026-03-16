-- =====================================================
-- 订单通知链路修复
-- 创建时间: 2026-03-15
-- 说明:
-- 1. 仅在缺失时补齐 order_confirm 模板骨架
-- 2. 仅在场景未绑定模板时补齐绑定关系
-- 3. 仅在场景未配置 data_mapping 时补齐默认映射
-- =====================================================

INSERT INTO `la_subscribe_message_template`
(
    `template_id`,
    `name`,
    `title`,
    `scene`,
    `content`,
    `example`,
    `keywords`,
    `category_id`,
    `status`,
    `sort`,
    `remark`,
    `create_time`,
    `update_time`
)
SELECT
    'TEMPLATE_ID_ORDER_CONFIRM',
    '订单确认通知',
    '订单确认通知',
    'order_confirm',
    '{"character_string1":{"key":"订单编号","value":""},"thing2":{"key":"确认状态","value":""},"amount3":{"key":"待付金额","value":""},"time4":{"key":"服务日期","value":""}}',
    '{"character_string1":"202603150001","thing2":"服务人员已确认","amount3":"2999.00","time4":"2026-03-20"}',
    '订单编号,确认状态,待付金额,服务日期',
    '',
    1,
    94,
    '订单全部确认后发送，需在微信后台申请模板后更新 template_id',
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_subscribe_message_template`
    WHERE `scene` = 'order_confirm'
      AND `delete_time` IS NULL
);

UPDATE `la_subscribe_message_scene` AS `scene`
JOIN `la_subscribe_message_template` AS `template`
  ON `template`.`scene` = 'order_confirm'
 AND `template`.`delete_time` IS NULL
SET
    `scene`.`template_id` = IF(`scene`.`template_id` = '', `template`.`template_id`, `scene`.`template_id`),
    `scene`.`data_mapping` = IF(
        `scene`.`data_mapping` IS NULL OR `scene`.`data_mapping` = '',
        '{"character_string1":"order_sn","thing2":"status_text","amount3":"pay_amount","time4":"service_date"}',
        `scene`.`data_mapping`
    ),
    `scene`.`update_time` = UNIX_TIMESTAMP()
WHERE `scene`.`scene` = 'order_confirm';
