-- 企微测试发送权限菜单（幂等）
SET @wecom_menu_id := (
    SELECT `id`
    FROM `la_system_menu`
    WHERE `perms` = 'setting.customer_service/getConfig'
    LIMIT 1
);

INSERT INTO `la_system_menu`
    (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT
    @wecom_menu_id, 'A', '发送企微测试消息', '', 0, 'setting.customer_service/testWecomMessage', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @wecom_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `la_system_menu`
      WHERE `perms` = 'setting.customer_service/testWecomMessage'
  );
