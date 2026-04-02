-- =====================================================
-- 候补失效订阅消息场景
-- 创建时间：2026-04-02
-- 说明：
-- 1. 新增候补失效订阅消息场景
-- 2. 新增候补失效订阅消息模板占位配置
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @upsert_waitlist_expired_scene_sql = IF(
    EXISTS(
        SELECT 1
        FROM `la_subscribe_message_scene`
        WHERE `scene` = 'waitlist_expired'
    ),
    'UPDATE `la_subscribe_message_scene`
        SET `name` = ''候补失效通知'',
            `description` = ''候补超过预约日期后通知用户'',
            `template_id` = ''TEMPLATE_ID_WAITLIST_EXPIRED'',
            `trigger_event` = ''WaitlistExpired'',
            `data_mapping` = ''{\"thing1\":\"staff_name\",\"time2\":\"schedule_date\",\"thing3\":\"package_name\",\"thing4\":\"remark\"}'',
            `page_path` = ''packages/pages/waitlist/waitlist'',
            `is_auto` = 1,
            `sort` = 99,
            `status` = 1,
            `update_time` = UNIX_TIMESTAMP()
      WHERE `scene` = ''waitlist_expired''',
    'INSERT INTO `la_subscribe_message_scene`
        (`scene`, `name`, `description`, `template_id`, `trigger_event`, `data_mapping`, `page_path`, `is_auto`, `sort`, `status`, `create_time`, `update_time`)
      VALUES
        (''waitlist_expired'', ''候补失效通知'', ''候补超过预约日期后通知用户'', ''TEMPLATE_ID_WAITLIST_EXPIRED'', ''WaitlistExpired'', ''{\"thing1\":\"staff_name\",\"time2\":\"schedule_date\",\"thing3\":\"package_name\",\"thing4\":\"remark\"}'', ''packages/pages/waitlist/waitlist'', 1, 99, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())'
);
PREPARE stmt FROM @upsert_waitlist_expired_scene_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @upsert_waitlist_expired_template_sql = IF(
    EXISTS(
        SELECT 1
        FROM `la_subscribe_message_template`
        WHERE `scene` = 'waitlist_expired'
          AND `delete_time` IS NULL
    ),
    'UPDATE `la_subscribe_message_template`
        SET `template_id` = ''TEMPLATE_ID_WAITLIST_EXPIRED'',
            `name` = ''候补失效通知'',
            `title` = ''候补失效'',
            `content` = ''{\"thing1\":{\"key\":\"服务人员\",\"value\":\"\"},\"time2\":{\"key\":\"档期日期\",\"value\":\"\"},\"thing3\":{\"key\":\"套餐名称\",\"value\":\"\"},\"thing4\":{\"key\":\"失效说明\",\"value\":\"\"}}'',
            `keywords` = ''服务人员,档期日期,套餐名称,失效说明'',
            `status` = 1,
            `sort` = 94,
            `remark` = ''候补失效后发送，需在微信后台申请模板后更新template_id'',
            `update_time` = UNIX_TIMESTAMP()
      WHERE `scene` = ''waitlist_expired''
        AND `delete_time` IS NULL',
    'INSERT INTO `la_subscribe_message_template`
        (`template_id`, `name`, `title`, `scene`, `content`, `keywords`, `status`, `sort`, `remark`, `create_time`, `update_time`)
      VALUES
        (''TEMPLATE_ID_WAITLIST_EXPIRED'', ''候补失效通知'', ''候补失效'', ''waitlist_expired'', ''{\"thing1\":{\"key\":\"服务人员\",\"value\":\"\"},\"time2\":{\"key\":\"档期日期\",\"value\":\"\"},\"thing3\":{\"key\":\"套餐名称\",\"value\":\"\"},\"thing4\":{\"key\":\"失效说明\",\"value\":\"\"}}'', ''服务人员,档期日期,套餐名称,失效说明'', 1, 94, ''候补失效后发送，需在微信后台申请模板后更新template_id'', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())'
);
PREPARE stmt FROM @upsert_waitlist_expired_template_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
