-- =============================================
-- 服务人员后台账号关联 + 功能开关 + 角色菜单
-- 创建日期: 2026-02-01
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. la_staff 增加 admin_id 关联后台账号
ALTER TABLE `la_staff`
    ADD COLUMN `admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联后台管理员ID' AFTER `user_id`,
    ADD KEY `idx_admin_id` (`admin_id`);

-- 2. 功能开关配置（feature_switch）
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'staff_center', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'staff_center'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'staff_admin', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'staff_admin'
);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'feature_switch', 'admin_dashboard', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'feature_switch' AND `name` = 'admin_dashboard'
);

-- 3. 新增角色：服务人员
INSERT INTO `la_system_role` (`name`, `desc`, `sort`, `create_time`, `update_time`)
SELECT '服务人员', '服务人员', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_role` WHERE `name` = '服务人员' AND `delete_time` IS NULL
);

SET @staff_role_id = (
    SELECT `id` FROM `la_system_role`
    WHERE `name` = '服务人员' AND `delete_time` IS NULL
    ORDER BY `id` DESC
    LIMIT 1
);

-- 4. 服务人员中心菜单
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 0, 'M', '服务人员中心', 'el-icon-User', 720, '', 'staff_center', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `paths` = 'staff_center' AND `type` = 'M'
);

SET @staff_center_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'staff_center' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

-- 我的资料
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '我的资料', '', 100, 'staff.staff/lists', 'profile', 'staff/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'profile'
);

-- 档期日历
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '档期日历', '', 90, 'schedule.schedule/lists', 'calendar', 'schedule/calendar/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'calendar'
);

-- 档期规则
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '档期规则', '', 80, 'schedule.scheduleRule/lists', 'rule', 'schedule/rule/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'rule'
);

-- 预约列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '预约列表', '', 70, 'schedule.booking/lists', 'booking', 'schedule/booking/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'booking'
);

-- 候补列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '候补列表', '', 60, 'schedule.waitlist/lists', 'waitlist', 'schedule/waitlist/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'waitlist'
);

-- 订单列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '订单列表', '', 50, 'order.order/lists', 'order', 'order/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'order'
);

-- 动态管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_center_menu_id, 'C', '动态管理', '', 40, 'dynamic.dynamic/lists', 'dynamic', 'dynamic/lists/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'dynamic'
);

-- 5. 角色关联菜单（服务人员）
-- 关联服务人员中心菜单及必要权限（若菜单已存在则仅建立关联）
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.pid = @staff_center_menu_id
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  );

-- 关联服务人员编辑页（隐藏菜单）
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.perms = 'staff.staff/add:edit'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  )
LIMIT 1;

-- 补充服务人员操作权限（若菜单存在则关联）
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.id
FROM `la_system_menu` m
WHERE m.perms IN (
    'staff.staff/detail',
    'staff.staff/edit',
    'schedule.schedule/monthCalendar',
    'schedule.schedule/detail',
    'schedule.schedule/setStatus',
    'schedule.schedule/batchSet',
    'schedule.schedule/unlock',
    'schedule.schedule/statistics',
    'schedule.schedule/lockRecords',
    'schedule.scheduleRule/detail',
    'schedule.scheduleRule/add',
    'schedule.scheduleRule/edit',
    'schedule.scheduleRule/delete',
    'schedule.scheduleRule/changeStatus',
    'schedule.scheduleRule/staffRule',
    'schedule.waitlist/detail',
    'schedule.waitlist/notify',
    'schedule.waitlist/batchNotify',
    'schedule.waitlist/convert',
    'schedule.waitlist/invalidate',
    'schedule.waitlist/statistics',
    'order.order/detail',
    'order.order/startService',
    'order.order/complete',
    'order.order/cancel',
    'order.order/auditVoucher',
    'order.order/logs',
    'order.order/addRemark',
    'order.order/statistics'
)
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.role_id = @staff_role_id AND rm.menu_id = m.id
  );

-- 6. 功能开关配置菜单（系统设置）
SET @setting_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'setting' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @setting_menu_id, 'C', '功能开关', 'el-icon-Switch', 55, 'setting.feature_switch/getConfig', 'feature_switch', 'setting/feature_switch/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @setting_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'setting.feature_switch/getConfig'
  );

-- 保存权限
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT m.id, 'A', '保存', '', 0, 'setting.feature_switch/setConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` m
WHERE m.perms = 'setting.feature_switch/getConfig'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'setting.feature_switch/setConfig'
  )
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
