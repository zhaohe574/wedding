-- 开放服务人员工作台移动端写能力开关（staff_center_write = 1）
-- 幂等执行：如已存在配置项则更新为 1，不存在则插入

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'staff_center_write', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `type`, `name` FROM `la_config`) AS existing
    WHERE existing.`type` = 'feature_switch' AND existing.`name` = 'staff_center_write'
);

UPDATE `la_config`
SET `value` = '1', `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'feature_switch' AND `name` = 'staff_center_write';
