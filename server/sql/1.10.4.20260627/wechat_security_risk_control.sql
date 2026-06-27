ALTER TABLE `la_user`
    ADD COLUMN `wechat_risk_rank` TINYINT NOT NULL DEFAULT 0 COMMENT '微信检测风险等级：0-4，数字越大风险越高' AFTER `total_recharge_amount`,
    ADD COLUMN `manual_risk_rank` TINYINT NOT NULL DEFAULT -1 COMMENT '后台人工风险等级：-1=跟随微信检测，0-4=人工覆盖' AFTER `wechat_risk_rank`,
    ADD COLUMN `risk_rank_update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '风险等级更新时间' AFTER `manual_risk_rank`;

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'wechat_text_check_enabled', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'wechat_text_check_enabled'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'wechat_text_check_profile_prob', '80', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'wechat_text_check_profile_prob'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'wechat_text_check_comment_prob', '70', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'wechat_text_check_comment_prob'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'wechat_text_check_review_as_hit', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'wechat_text_check_review_as_hit'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'risk_control', 'rank_permissions', '{"0":{"enabled_abilities":["profile","content_publish","comment","interaction","activity","schedule","order","payment","recharge","after_sale","customer_service","upload","notification","staff_center"],"comment_force_review":false},"1":{"enabled_abilities":["profile","content_publish","comment","interaction","activity","schedule","order","payment","recharge","after_sale","customer_service","upload","notification","staff_center"],"comment_force_review":false},"2":{"enabled_abilities":["profile","content_publish","comment","interaction","activity","schedule","order","payment","recharge","after_sale","customer_service","upload","notification","staff_center"],"comment_force_review":true},"3":{"enabled_abilities":[],"comment_force_review":false},"4":{"enabled_abilities":[],"comment_force_review":false}}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'risk_control' AND `name` = 'rank_permissions'
);
