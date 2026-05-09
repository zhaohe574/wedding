-- =============================================
-- 婚庆服务预约系统服务人员结算去红包化热更新脚本
-- 版本：2.0.0.20260509
--
-- 适用场景：
-- 1. 已上线环境从“微信红包结算”切换为“微信商家转账到零钱”。
-- 2. 保留历史红包明细表和历史审计数据，不删除旧表。
-- 3. 历史处于“红包待领取”的结算转为失败/待人工处理，避免被新代码误识别为转账处理中。
--
-- 注意：
-- 1. 本脚本可重复执行。
-- 2. 商家转账配置默认 disabled，需在后台配置场景 ID、微信支付公钥后再开启。
-- =============================================

SET NAMES utf8mb4;
SET @now := UNIX_TIMESTAMP();
SET @settlement_menu_id := NULL;
SET @has_retry_transfer := 0;
SET @has_sync_transfer := 0;
SET @has_transfer_detail := 0;
SET @has_transfer_config := 0;
SET @has_save_transfer_config := 0;

-- 新增服务人员结算转账明细表，保留原红包明细表作为历史只读数据。
CREATE TABLE IF NOT EXISTS `la_staff_settlement_transfer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `settlement_id` INT UNSIGNED NOT NULL COMMENT '结算记录ID',
    `settlement_sn` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '结算编号',
    `staff_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单ID',
    `order_item_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单项ID',
    `out_bill_no` VARCHAR(32) NOT NULL COMMENT '商户转账单号',
    `transfer_bill_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '微信转账单号',
    `mch_id` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '微信商户号',
    `appid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '小程序AppID',
    `openid` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '收款openid',
    `user_name` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '收款实名姓名',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '转账金额',
    `amount_fen` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '转账金额分',
    `transfer_scene_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '转账场景ID',
    `transfer_remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '转账备注',
    `user_recv_perception` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '到账感知文案',
    `package_info` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '确认收款package_info',
    `wx_state` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '微信转账状态',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待发起,1=转账中,2=待用户确认,3=已到账,4=失败,5=已关闭',
    `request_data` TEXT DEFAULT NULL COMMENT '请求数据',
    `response_data` TEXT DEFAULT NULL COMMENT '发起响应',
    `query_response` TEXT DEFAULT NULL COMMENT '查询响应',
    `notify_data` TEXT DEFAULT NULL COMMENT '回调数据',
    `fail_reason` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `retry_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
    `send_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发起时间',
    `success_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '到账时间',
    `close_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关闭时间',
    `last_query_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后查询时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_out_bill_no` (`out_bill_no`),
    KEY `idx_settlement_id` (`settlement_id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_transfer_bill_no` (`transfer_bill_no`),
    KEY `idx_status_query` (`status`, `last_query_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员结算转账明细表';

-- 更新结算状态注释：4 从“红包待领取”改为“转账处理中/待确认”。
ALTER TABLE `la_staff_settlement`
    MODIFY COLUMN `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=待结算,1=已结算,2=已取消,3=失败,4=转账处理中/待确认';

-- 历史红包待领取记录不再继续发红包，转为人工确认处理。
UPDATE `la_staff_settlement` AS s
LEFT JOIN `la_staff_settlement_transfer` AS t ON t.`settlement_id` = s.`id`
SET s.`status` = 3,
    s.`fail_reason` = CASE
        WHEN s.`fail_reason` = '' THEN '历史红包结算已下线，请人工确认处理'
        ELSE s.`fail_reason`
    END,
    s.`update_time` = @now
WHERE s.`status` = 4
  AND t.`id` IS NULL;

-- 关闭旧红包配置，保留历史配置记录。
UPDATE `la_config`
SET `value` = '0',
    `update_time` = @now
WHERE `type` = 'staff_settlement_red_packet'
  AND `name` = 'enabled';

-- 写入微信商家转账配置，默认不开启，避免热更后未配置即自动出款。
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'enabled', '0', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'enabled'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'auto_send', '1', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'auto_send'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'transfer_scene_id', '', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'transfer_scene_id'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'transfer_remark', '服务人员结算', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'transfer_remark'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'user_recv_perception', '服务结算', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'user_recv_perception'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'quota_hint', '单笔转账额度以微信商户平台配置为准，金额达到实名校验阈值时必须配置收款实名。', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'quota_hint'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'manual_fallback', '1', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'manual_fallback'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'amount_name_threshold', '2000', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'amount_name_threshold'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'wechatpay_serial', '', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'wechatpay_serial'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'wechatpay_public_key', '', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'wechatpay_public_key'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'staff_settlement_transfer', 'transfer_scene_report_infos', '[{"info_type":"岗位","info_content":"服务人员"}]', @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config`
    WHERE `type` = 'staff_settlement_transfer' AND `name` = 'transfer_scene_report_infos'
);

-- 更新自动结算定时任务说明。
UPDATE `la_dev_crontab`
SET `remark` = '每分钟生成已完成已付订单的服务人员结算，并处理微信商家转账状态',
    `update_time` = @now
WHERE `command` = 'auto_staff_settlement';

-- 解析结算管理菜单 ID；若线上菜单 ID 有偏差，优先使用实际菜单。
SELECT @settlement_menu_id := `id`
FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/lists'
LIMIT 1;

SET @settlement_menu_id := IFNULL(@settlement_menu_id, 199);

-- 红包权限点迁移为转账权限点。如果新权限点已存在，则旧红包权限点下线。
SELECT @has_retry_transfer := COUNT(*)
FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/retryTransfer';

UPDATE `la_system_menu`
SET `pid` = @settlement_menu_id,
    `name` = '重试转账',
    `perms` = 'finance.settlement/retryTransfer',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/retryRedPacket'
  AND @has_retry_transfer = 0;

UPDATE `la_system_menu`
SET `name` = '已下线-重试红包',
    `is_show` = 0,
    `is_disable` = 1,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/retryRedPacket';

INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @settlement_menu_id, 'A', '重试转账', '', 0, 'finance.settlement/retryTransfer', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `perms` = 'finance.settlement/retryTransfer'
);

SELECT @has_sync_transfer := COUNT(*)
FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/syncTransfer';

UPDATE `la_system_menu`
SET `pid` = @settlement_menu_id,
    `name` = '同步转账状态',
    `perms` = 'finance.settlement/syncTransfer',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/syncRedPacket'
  AND @has_sync_transfer = 0;

UPDATE `la_system_menu`
SET `name` = '已下线-同步红包状态',
    `is_show` = 0,
    `is_disable` = 1,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/syncRedPacket';

INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @settlement_menu_id, 'A', '同步转账状态', '', 0, 'finance.settlement/syncTransfer', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `perms` = 'finance.settlement/syncTransfer'
);

SELECT @has_transfer_detail := COUNT(*)
FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/transferDetail';

UPDATE `la_system_menu`
SET `pid` = @settlement_menu_id,
    `name` = '转账明细',
    `perms` = 'finance.settlement/transferDetail',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/redPacketDetail'
  AND @has_transfer_detail = 0;

UPDATE `la_system_menu`
SET `name` = '已下线-红包明细',
    `is_show` = 0,
    `is_disable` = 1,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/redPacketDetail';

INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @settlement_menu_id, 'A', '转账明细', '', 0, 'finance.settlement/transferDetail', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `perms` = 'finance.settlement/transferDetail'
);

SELECT @has_transfer_config := COUNT(*)
FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/transferConfig';

UPDATE `la_system_menu`
SET `pid` = @settlement_menu_id,
    `name` = '转账配置',
    `perms` = 'finance.settlement/transferConfig',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/redPacketConfig'
  AND @has_transfer_config = 0;

UPDATE `la_system_menu`
SET `name` = '已下线-红包配置',
    `is_show` = 0,
    `is_disable` = 1,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/redPacketConfig';

INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @settlement_menu_id, 'A', '转账配置', '', 0, 'finance.settlement/transferConfig', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `perms` = 'finance.settlement/transferConfig'
);

SELECT @has_save_transfer_config := COUNT(*)
FROM `la_system_menu`
WHERE `perms` = 'finance.settlement/saveTransferConfig';

UPDATE `la_system_menu`
SET `pid` = @settlement_menu_id,
    `name` = '保存转账配置',
    `perms` = 'finance.settlement/saveTransferConfig',
    `is_show` = 0,
    `is_disable` = 0,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/saveRedPacketConfig'
  AND @has_save_transfer_config = 0;

UPDATE `la_system_menu`
SET `name` = '已下线-保存红包配置',
    `is_show` = 0,
    `is_disable` = 1,
    `update_time` = @now
WHERE `perms` = 'finance.settlement/saveRedPacketConfig';

INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @settlement_menu_id, 'A', '保存转账配置', '', 0, 'finance.settlement/saveTransferConfig', '', '', '', '', 0, 0, 0, @now, @now
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `perms` = 'finance.settlement/saveTransferConfig'
);
