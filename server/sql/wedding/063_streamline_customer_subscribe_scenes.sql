SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @now := UNIX_TIMESTAMP();

SET @order_confirm_template_id := COALESCE(
    (
        SELECT `template_id`
        FROM `la_subscribe_message_template`
        WHERE `scene` = 'order_confirm'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
          AND (`delete_time` IS NULL OR `delete_time` = 0)
        ORDER BY `id` DESC
        LIMIT 1
    ),
    (
        SELECT `template_id`
        FROM `la_subscribe_message_scene`
        WHERE `scene` = 'order_confirm'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
        ORDER BY `id` DESC
        LIMIT 1
    ),
    'TEMPLATE_ID_ORDER_CONFIRM'
);

SET @schedule_remind_template_id := COALESCE(
    (
        SELECT `template_id`
        FROM `la_subscribe_message_template`
        WHERE `scene` = 'schedule_remind'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
          AND (`delete_time` IS NULL OR `delete_time` = 0)
        ORDER BY `id` DESC
        LIMIT 1
    ),
    (
        SELECT `template_id`
        FROM `la_subscribe_message_scene`
        WHERE `scene` = 'schedule_remind'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
        ORDER BY `id` DESC
        LIMIT 1
    ),
    'TEMPLATE_ID_SERVICE_REMIND'
);

SET @refund_result_template_id := COALESCE(
    (
        SELECT `template_id`
        FROM `la_subscribe_message_template`
        WHERE `scene` = 'refund_result'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
          AND (`delete_time` IS NULL OR `delete_time` = 0)
        ORDER BY `id` DESC
        LIMIT 1
    ),
    (
        SELECT `template_id`
        FROM `la_subscribe_message_scene`
        WHERE `scene` = 'refund_result'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
        ORDER BY `id` DESC
        LIMIT 1
    ),
    'TEMPLATE_ID_REFUND_RESULT'
);

SET @ticket_update_template_id := COALESCE(
    (
        SELECT `template_id`
        FROM `la_subscribe_message_template`
        WHERE `scene` = 'ticket_update'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
          AND (`delete_time` IS NULL OR `delete_time` = 0)
        ORDER BY `id` DESC
        LIMIT 1
    ),
    (
        SELECT `template_id`
        FROM `la_subscribe_message_scene`
        WHERE `scene` = 'ticket_update'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
        ORDER BY `id` DESC
        LIMIT 1
    ),
    'TEMPLATE_ID_TICKET_UPDATE'
);

SET @waitlist_template_id := COALESCE(
    (
        SELECT `template_id`
        FROM `la_subscribe_message_template`
        WHERE `scene` = 'waitlist_release'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
          AND (`delete_time` IS NULL OR `delete_time` = 0)
        ORDER BY `id` DESC
        LIMIT 1
    ),
    (
        SELECT `template_id`
        FROM `la_subscribe_message_scene`
        WHERE `scene` = 'waitlist_release'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
        ORDER BY `id` DESC
        LIMIT 1
    ),
    (
        SELECT `template_id`
        FROM `la_subscribe_message_template`
        WHERE `scene` = 'waitlist_expired'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
          AND (`delete_time` IS NULL OR `delete_time` = 0)
        ORDER BY `id` DESC
        LIMIT 1
    ),
    (
        SELECT `template_id`
        FROM `la_subscribe_message_scene`
        WHERE `scene` = 'waitlist_expired'
          AND `template_id` <> ''
          AND LEFT(`template_id`, 12) <> 'TEMPLATE_ID_'
        ORDER BY `id` DESC
        LIMIT 1
    ),
    'TEMPLATE_ID_WAITLIST_RELEASE'
);

DELETE FROM `la_subscribe_message_scene`
WHERE `scene` IN ('order_create', 'order_paid', 'order_complete', 'callback_remind', 'change_result', 'schedule_change');

INSERT INTO `la_subscribe_message_scene`
(`scene`, `name`, `description`, `template_id`, `trigger_event`, `data_mapping`, `page_path`, `is_auto`, `delay_seconds`, `status`, `sort`, `create_time`, `update_time`)
VALUES
('order_confirm', '订单确认通知', '订单确认后通知用户', @order_confirm_template_id, 'OrderConfirmed', '{"character_string1":"order_sn","thing2":"status_text","amount3":"pay_amount","time4":"service_date"}', 'pages/order_detail/order_detail', 1, 0, 1, 110, @now, @now),
('schedule_remind', '服务提醒通知', '服务开始前提醒用户', @schedule_remind_template_id, 'ScheduleRemind', '{"thing1":"service_name","time2":"service_date","thing3":"address","thing4":"staff_name"}', 'pages/order_detail/order_detail', 1, 0, 1, 109, @now, @now),
('refund_result', '退款结果通知', '退款审核结果通知', @refund_result_template_id, 'RefundProcessed', '{"character_string1":"order_sn","amount2":"refund_amount","phrase3":"status_text","thing4":"reason"}', 'pages/order_detail/order_detail', 1, 0, 1, 108, @now, @now),
('ticket_update', '工单进度通知', '售后工单状态更新通知', @ticket_update_template_id, 'TicketUpdated', '{"character_string1":"ticket_sn","phrase2":"status_text","thing3":"handle_note","time4":"update_time"}', 'packages/pages/aftersale/ticket_detail', 1, 0, 1, 107, @now, @now),
('waitlist_release', '候补释放通知', '档期释放后通知候补用户', @waitlist_template_id, 'WaitlistReleased', '{"thing1":"staff_name","time2":"schedule_date","thing3":"package_name","thing4":"status_text"}', 'packages/pages/waitlist/waitlist', 1, 0, 1, 106, @now, @now),
('waitlist_expired', '候补失效通知', '候补超过预约日期后通知用户', @waitlist_template_id, 'WaitlistExpired', '{"thing1":"staff_name","time2":"schedule_date","thing3":"package_name","thing4":"status_text"}', 'packages/pages/waitlist/waitlist', 1, 0, 1, 105, @now, @now)
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`description` = VALUES(`description`),
`template_id` = VALUES(`template_id`),
`trigger_event` = VALUES(`trigger_event`),
`data_mapping` = VALUES(`data_mapping`),
`page_path` = VALUES(`page_path`),
`is_auto` = VALUES(`is_auto`),
`delay_seconds` = VALUES(`delay_seconds`),
`status` = VALUES(`status`),
`sort` = VALUES(`sort`),
`update_time` = VALUES(`update_time`);

UPDATE `la_subscribe_message_template`
SET `status` = 0,
    `remark` = CONCAT(IFNULL(NULLIF(`remark`, ''), '历史模板'), '（已从客户订阅消息下线）'),
    `update_time` = @now
WHERE `scene` IN ('order_create', 'order_paid', 'order_complete', 'callback_remind', 'change_result', 'schedule_change')
  AND (`delete_time` IS NULL OR `delete_time` = 0);

UPDATE `la_subscribe_message_template`
SET `scene` = 'waitlist_release',
    `name` = '候补状态通知',
    `title` = '候补状态通知',
    `content` = '{"thing1":{"key":"服务人员","value":""},"time2":{"key":"档期日期","value":""},"thing3":{"key":"套餐名称","value":""},"thing4":{"key":"状态说明","value":""}}',
    `keywords` = '服务人员,档期日期,套餐名称,状态说明',
    `status` = 1,
    `sort` = 96,
    `remark` = '候补释放或失效时发送，需在微信后台申请模板后更新template_id',
    `update_time` = @now
WHERE `template_id` = @waitlist_template_id
  AND (`delete_time` IS NULL OR `delete_time` = 0);

UPDATE `la_subscribe_message_template`
SET `status` = 0,
    `remark` = CONCAT(IFNULL(NULLIF(`remark`, ''), '历史模板'), '（已合并至候补状态通知）'),
    `update_time` = @now
WHERE `scene` = 'waitlist_release'
  AND `template_id` <> @waitlist_template_id
  AND (`delete_time` IS NULL OR `delete_time` = 0);

UPDATE `la_subscribe_message_template`
SET `name` = '订单确认通知',
    `title` = '订单确认通知',
    `content` = '{"character_string1":{"key":"订单编号","value":""},"thing2":{"key":"确认状态","value":""},"amount3":{"key":"订单金额","value":""},"time4":{"key":"服务日期","value":""}}',
    `keywords` = '订单编号,确认状态,订单金额,服务日期',
    `status` = 1,
    `sort` = 100,
    `remark` = '订单确认后发送，需在微信后台申请模板后更新template_id',
    `update_time` = @now
WHERE `scene` = 'order_confirm'
  AND (`delete_time` IS NULL OR `delete_time` = 0);

INSERT INTO `la_subscribe_message_template`
(`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`)
SELECT @order_confirm_template_id, '订单确认通知', '订单确认通知', 'order_confirm', '{"character_string1":{"key":"订单编号","value":""},"thing2":{"key":"确认状态","value":""},"amount3":{"key":"订单金额","value":""},"time4":{"key":"服务日期","value":""}}', '订单编号,确认状态,订单金额,服务日期', 1, 100, '订单确认后发送，需在微信后台申请模板后更新template_id', @now, @now
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_subscribe_message_template`
    WHERE `scene` = 'order_confirm'
      AND (`delete_time` IS NULL OR `delete_time` = 0)
);

UPDATE `la_subscribe_message_template`
SET `name` = '服务提醒通知',
    `title` = '服务提醒',
    `content` = '{"thing1":{"key":"服务内容","value":""},"time2":{"key":"服务时间","value":""},"thing3":{"key":"服务地点","value":""},"thing4":{"key":"服务人员","value":""}}',
    `keywords` = '服务内容,服务时间,服务地点,服务人员',
    `status` = 1,
    `sort` = 99,
    `remark` = '服务开始前提醒，需在微信后台申请模板后更新template_id',
    `update_time` = @now
WHERE `scene` = 'schedule_remind'
  AND (`delete_time` IS NULL OR `delete_time` = 0);

INSERT INTO `la_subscribe_message_template`
(`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`)
SELECT @schedule_remind_template_id, '服务提醒通知', '服务提醒', 'schedule_remind', '{"thing1":{"key":"服务内容","value":""},"time2":{"key":"服务时间","value":""},"thing3":{"key":"服务地点","value":""},"thing4":{"key":"服务人员","value":""}}', '服务内容,服务时间,服务地点,服务人员', 1, 99, '服务开始前提醒，需在微信后台申请模板后更新template_id', @now, @now
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_subscribe_message_template`
    WHERE `scene` = 'schedule_remind'
      AND (`delete_time` IS NULL OR `delete_time` = 0)
);

UPDATE `la_subscribe_message_template`
SET `name` = '退款结果通知',
    `title` = '退款通知',
    `content` = '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"退款金额","value":""},"phrase3":{"key":"退款状态","value":""},"thing4":{"key":"退款原因","value":""}}',
    `keywords` = '订单编号,退款金额,退款状态,退款原因',
    `status` = 1,
    `sort` = 98,
    `remark` = '退款审核后发送，需在微信后台申请模板后更新template_id',
    `update_time` = @now
WHERE `scene` = 'refund_result'
  AND (`delete_time` IS NULL OR `delete_time` = 0);

INSERT INTO `la_subscribe_message_template`
(`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`)
SELECT @refund_result_template_id, '退款结果通知', '退款通知', 'refund_result', '{"character_string1":{"key":"订单编号","value":""},"amount2":{"key":"退款金额","value":""},"phrase3":{"key":"退款状态","value":""},"thing4":{"key":"退款原因","value":""}}', '订单编号,退款金额,退款状态,退款原因', 1, 98, '退款审核后发送，需在微信后台申请模板后更新template_id', @now, @now
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_subscribe_message_template`
    WHERE `scene` = 'refund_result'
      AND (`delete_time` IS NULL OR `delete_time` = 0)
);

UPDATE `la_subscribe_message_template`
SET `name` = '工单进度通知',
    `title` = '工单状态更新',
    `content` = '{"character_string1":{"key":"工单编号","value":""},"phrase2":{"key":"工单状态","value":""},"thing3":{"key":"处理说明","value":""},"time4":{"key":"更新时间","value":""}}',
    `keywords` = '工单编号,工单状态,处理说明,更新时间',
    `status` = 1,
    `sort` = 97,
    `remark` = '工单状态变更时发送，需在微信后台申请模板后更新template_id',
    `update_time` = @now
WHERE `scene` = 'ticket_update'
  AND (`delete_time` IS NULL OR `delete_time` = 0);

INSERT INTO `la_subscribe_message_template`
(`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`)
SELECT @ticket_update_template_id, '工单进度通知', '工单状态更新', 'ticket_update', '{"character_string1":{"key":"工单编号","value":""},"phrase2":{"key":"工单状态","value":""},"thing3":{"key":"处理说明","value":""},"time4":{"key":"更新时间","value":""}}', '工单编号,工单状态,处理说明,更新时间', 1, 97, '工单状态变更时发送，需在微信后台申请模板后更新template_id', @now, @now
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_subscribe_message_template`
    WHERE `scene` = 'ticket_update'
      AND (`delete_time` IS NULL OR `delete_time` = 0)
);

INSERT INTO `la_subscribe_message_template`
(`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`)
SELECT @waitlist_template_id, '候补状态通知', '候补状态通知', 'waitlist_release', '{"thing1":{"key":"服务人员","value":""},"time2":{"key":"档期日期","value":""},"thing3":{"key":"套餐名称","value":""},"thing4":{"key":"状态说明","value":""}}', '服务人员,档期日期,套餐名称,状态说明', 1, 96, '候补释放或失效时发送，需在微信后台申请模板后更新template_id', @now, @now
WHERE NOT EXISTS (
    SELECT 1
    FROM `la_subscribe_message_template`
    WHERE `template_id` = @waitlist_template_id
      AND (`delete_time` IS NULL OR `delete_time` = 0)
);

UPDATE `la_subscribe_message_template`
SET `status` = 0,
    `remark` = CONCAT(IFNULL(NULLIF(`remark`, ''), '历史模板'), '（已合并至候补状态通知）'),
    `update_time` = @now
WHERE `scene` = 'waitlist_expired'
  AND `template_id` <> @waitlist_template_id
  AND (`delete_time` IS NULL OR `delete_time` = 0);

SET FOREIGN_KEY_CHECKS = 1;
