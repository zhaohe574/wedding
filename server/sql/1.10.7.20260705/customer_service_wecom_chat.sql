-- 小程序客服改为微信客服会话。
-- 仅停用旧公开联系方式配置，不删除生产历史字段。

START TRANSACTION;

UPDATE `la_config`
SET `value` = '',
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'customer_service'
  AND `name` IN ('qr_code', 'wechat', 'phone', 'contact_link');

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'customer_service', 'service_time', '工作日 09:00 - 18:00', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'customer_service' AND `name` = 'service_time'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'customer_service', 'tips', '进入微信客服后可继续沟通', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'customer_service' AND `name` = 'tips'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'customer_service', 'wecom_enabled', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'customer_service' AND `name` = 'wecom_enabled'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'customer_service', 'wecom_corp_id', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'customer_service' AND `name` = 'wecom_corp_id'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'customer_service', 'wecom_secret', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'customer_service' AND `name` = 'wecom_secret'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'customer_service', 'wecom_agent_id', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'customer_service' AND `name` = 'wecom_agent_id'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'customer_service', 'wecom_card_mode', 'mini_first', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'customer_service' AND `name` = 'wecom_card_mode'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'customer_service', 'wecom_aftersale_userids', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'customer_service' AND `name` = 'wecom_aftersale_userids'
);

COMMIT;
