-- 服务人员自动微信红包结算：现有库幂等升级脚本
-- 执行前请确认已在微信商户平台开通商家红包，并配置微信支付 API 证书、商户号、API 密钥和小程序 AppID。

ALTER TABLE `la_staff_settlement`
    MODIFY COLUMN `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待结算,1=已结算,2=已取消,3=失败,4=红包待领取/处理中';

CREATE TABLE IF NOT EXISTS `la_staff_settlement_red_packet` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `settlement_id` INT UNSIGNED NOT NULL COMMENT '结算记录ID',
    `settlement_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '结算编号',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单项ID',
    `mch_billno` VARCHAR(32) NOT NULL COMMENT '微信红包商户单号',
    `mch_id` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '微信商户号',
    `wxappid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '小程序AppID',
    `openid` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '收款openid',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '红包金额',
    `amount_fen` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '红包金额分',
    `total_num` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '红包个数',
    `send_name` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '发送方名称',
    `wishing` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '祝福语',
    `act_name` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '活动名称',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `scene_id` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '场景ID',
    `wx_hb_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信红包单号',
    `receive_package` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '小程序领取package',
    `wx_status` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '微信状态',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待发放,1=发放中,2=待领取,3=已领取,4=已退款,5=失败',
    `request_data` TEXT DEFAULT NULL COMMENT '请求数据',
    `response_data` TEXT DEFAULT NULL COMMENT '发放响应',
    `query_response` TEXT DEFAULT NULL COMMENT '查询响应',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `retry_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
    `send_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放时间',
    `receive_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取时间',
    `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款时间',
    `last_query_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后查询时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_mch_billno` (`mch_billno`),
    KEY `idx_settlement_id` (`settlement_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_status_query` (`status`, `last_query_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算红包明细表';

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'enabled', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'enabled');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'auto_send', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'auto_send');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'send_name', '服务结算', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'send_name');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'wishing', '感谢你的专业服务', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'wishing');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'act_name', '服务人员结算', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'act_name');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'remark', '服务人员结算红包', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'remark');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'scene_id', 'PRODUCT_5', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'scene_id');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'notify_way', 'MINI_PROGRAM_JSAPI', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'notify_way');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'max_amount', '200', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'max_amount');
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_red_packet', 'min_amount', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'staff_settlement_red_packet' AND `name` = 'min_amount');

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`, `delete_time`)
SELECT '服务人员自动结算', 1, 1, '每分钟生成已完成已付订单的服务人员结算，并处理微信红包发放与领取状态', 'auto_staff_settlement', '', 1, '* * * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'auto_staff_settlement');

INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 199, 'A', '手动生成结算', '', 0, 'finance.settlement/generate', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'finance.settlement/generate');
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 199, 'A', '重试红包', '', 0, 'finance.settlement/retryRedPacket', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'finance.settlement/retryRedPacket');
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 199, 'A', '同步红包状态', '', 0, 'finance.settlement/syncRedPacket', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'finance.settlement/syncRedPacket');
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 199, 'A', '红包明细', '', 0, 'finance.settlement/redPacketDetail', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'finance.settlement/redPacketDetail');
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 199, 'A', '红包配置', '', 0, 'finance.settlement/redPacketConfig', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'finance.settlement/redPacketConfig');
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 199, 'A', '保存红包配置', '', 0, 'finance.settlement/saveRedPacketConfig', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'finance.settlement/saveRedPacketConfig');
