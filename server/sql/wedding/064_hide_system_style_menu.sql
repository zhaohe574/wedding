-- 隐藏已下线的系统风格页，避免后台继续生成该装修入口。
UPDATE `la_system_menu`
SET `is_show` = 0,
    `is_disable` = 1,
    `update_time` = UNIX_TIMESTAMP()
WHERE `component` = 'decoration/style/style';
