-- =============================================
-- 服务人员中心专属页面/接口/权限改造（幂等）
-- 创建日期: 2026-02-28
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 0) 确保服务人员中心一级菜单存在
INSERT INTO `la_system_menu` (
    `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
    `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`
)
SELECT
    0, 'M', '服务人员中心', 'el-icon-User', 350, '', 'staff_center', '', '',
    '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `paths` = 'staff_center' AND `type` = 'M'
);

SET @staff_center_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'staff_center' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

-- 1) 保障 7 个子菜单存在
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @staff_center_menu_id, 'C', '我的资料', '', 100, 'staff.staff/myProfile', 'profile', 'staff_center/profile/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_center_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'profile' AND `type` = 'C'
  );

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @staff_center_menu_id, 'C', '档期日历', '', 90, 'schedule.schedule/myCalendar', 'calendar', 'staff_center/calendar/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_center_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'calendar' AND `type` = 'C'
  );

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @staff_center_menu_id, 'C', '档期规则', '', 80, 'schedule.scheduleRule/myRules', 'rule', 'staff_center/rule/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_center_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'rule' AND `type` = 'C'
  );

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @staff_center_menu_id, 'C', '预约列表', '', 70, 'schedule.booking/myBookings', 'booking', 'staff_center/booking/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_center_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'booking' AND `type` = 'C'
  );

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @staff_center_menu_id, 'C', '候补列表', '', 60, 'schedule.waitlist/myWaitlist', 'waitlist', 'staff_center/waitlist/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_center_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'waitlist' AND `type` = 'C'
  );

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @staff_center_menu_id, 'C', '订单列表', '', 50, 'order.order/myOrders', 'order', 'staff_center/order/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_center_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'order' AND `type` = 'C'
  );

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @staff_center_menu_id, 'C', '动态管理', '', 40, 'dynamic.dynamic/myDynamics', 'dynamic', 'staff_center/dynamic/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_center_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'dynamic' AND `type` = 'C'
  );

-- 2) 更新 7 个子菜单为专属 component + perms
UPDATE `la_system_menu`
SET `component` = 'staff_center/profile/index', `perms` = 'staff.staff/myProfile', `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = @staff_center_menu_id AND `paths` = 'profile' AND `type` = 'C';

UPDATE `la_system_menu`
SET `component` = 'staff_center/calendar/index', `perms` = 'schedule.schedule/myCalendar', `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = @staff_center_menu_id AND `paths` = 'calendar' AND `type` = 'C';

UPDATE `la_system_menu`
SET `component` = 'staff_center/rule/index', `perms` = 'schedule.scheduleRule/myRules', `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = @staff_center_menu_id AND `paths` = 'rule' AND `type` = 'C';

UPDATE `la_system_menu`
SET `component` = 'staff_center/booking/index', `perms` = 'schedule.booking/myBookings', `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = @staff_center_menu_id AND `paths` = 'booking' AND `type` = 'C';

UPDATE `la_system_menu`
SET `component` = 'staff_center/waitlist/index', `perms` = 'schedule.waitlist/myWaitlist', `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = @staff_center_menu_id AND `paths` = 'waitlist' AND `type` = 'C';

UPDATE `la_system_menu`
SET `component` = 'staff_center/order/index', `perms` = 'order.order/myOrders', `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = @staff_center_menu_id AND `paths` = 'order' AND `type` = 'C';

UPDATE `la_system_menu`
SET `component` = 'staff_center/dynamic/index', `perms` = 'dynamic.dynamic/myDynamics', `update_time` = UNIX_TIMESTAMP()
WHERE `pid` = @staff_center_menu_id AND `paths` = 'dynamic' AND `type` = 'C';

SET @menu_profile_id = (
    SELECT `id` FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'profile' AND `type` = 'C' LIMIT 1
);
SET @menu_calendar_id = (
    SELECT `id` FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'calendar' AND `type` = 'C' LIMIT 1
);
SET @menu_rule_id = (
    SELECT `id` FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'rule' AND `type` = 'C' LIMIT 1
);
SET @menu_booking_id = (
    SELECT `id` FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'booking' AND `type` = 'C' LIMIT 1
);
SET @menu_waitlist_id = (
    SELECT `id` FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'waitlist' AND `type` = 'C' LIMIT 1
);
SET @menu_order_id = (
    SELECT `id` FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'order' AND `type` = 'C' LIMIT 1
);
SET @menu_dynamic_id = (
    SELECT `id` FROM `la_system_menu` WHERE `pid` = @staff_center_menu_id AND `paths` = 'dynamic' AND `type` = 'C' LIMIT 1
);

-- 3) 补齐 my* 按钮权限菜单（A）

-- 我的资料
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '保存资料', '', 0, 'staff.staff/myProfileUpdate', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileUpdate');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '套餐配置详情', '', 0, 'staff.staff/myProfilePackageConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfilePackageConfig');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '更新套餐配置', '', 0, 'staff.staff/myProfileUpdatePackageConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileUpdatePackageConfig');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '创建专属套餐', '', 0, 'staff.staff/myProfileCreatePackage', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileCreatePackage');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '编辑专属套餐', '', 0, 'staff.staff/myProfileUpdateStaffPackage', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileUpdateStaffPackage');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '删除专属套餐', '', 0, 'staff.staff/myProfileDeletePackage', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileDeletePackage');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '轮播图列表', '', 0, 'staff.staff/myProfileBannerList', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileBannerList');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '新增轮播图', '', 0, 'staff.staff/myProfileBannerAdd', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileBannerAdd');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '编辑轮播图', '', 0, 'staff.staff/myProfileBannerEdit', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileBannerEdit');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '删除轮播图', '', 0, 'staff.staff/myProfileBannerDelete', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileBannerDelete');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '轮播图排序', '', 0, 'staff.staff/myProfileBannerSort', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileBannerSort');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_profile_id, 'A', '轮播图配置', '', 0, 'staff.staff/myProfileBannerConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_profile_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'staff.staff/myProfileBannerConfig');

-- 档期日历
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_calendar_id, 'A', '设置档期', '', 0, 'schedule.schedule/myCalendarSetStatus', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_calendar_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.schedule/myCalendarSetStatus');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_calendar_id, 'A', '批量设置档期', '', 0, 'schedule.schedule/myCalendarBatchSet', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_calendar_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.schedule/myCalendarBatchSet');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_calendar_id, 'A', '释放档期', '', 0, 'schedule.schedule/myCalendarUnlock', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_calendar_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.schedule/myCalendarUnlock');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_calendar_id, 'A', '档期统计', '', 0, 'schedule.schedule/myCalendarStatistics', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_calendar_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.schedule/myCalendarStatistics');

-- 档期规则
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_rule_id, 'A', '规则详情', '', 0, 'schedule.scheduleRule/myRuleDetail', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_rule_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.scheduleRule/myRuleDetail');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_rule_id, 'A', '保存规则', '', 0, 'schedule.scheduleRule/myRuleSave', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_rule_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.scheduleRule/myRuleSave');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_rule_id, 'A', '删除规则', '', 0, 'schedule.scheduleRule/myRuleDelete', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_rule_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.scheduleRule/myRuleDelete');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_rule_id, 'A', '规则状态', '', 0, 'schedule.scheduleRule/myRuleChangeStatus', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_rule_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.scheduleRule/myRuleChangeStatus');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_rule_id, 'A', '规则模板', '', 0, 'schedule.scheduleRule/myRuleTemplate', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_rule_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.scheduleRule/myRuleTemplate');

-- 预约列表
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_booking_id, 'A', '预约详情', '', 0, 'schedule.booking/myBookingDetail', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_booking_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.booking/myBookingDetail');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_booking_id, 'A', '确认预约', '', 0, 'schedule.booking/myBookingConfirm', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_booking_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.booking/myBookingConfirm');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_booking_id, 'A', '取消预约', '', 0, 'schedule.booking/myBookingCancel', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_booking_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.booking/myBookingCancel');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_booking_id, 'A', '预约统计', '', 0, 'schedule.booking/myBookingStatistics', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_booking_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.booking/myBookingStatistics');

-- 候补列表
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_waitlist_id, 'A', '候补详情', '', 0, 'schedule.waitlist/myWaitlistDetail', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_waitlist_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.waitlist/myWaitlistDetail');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_waitlist_id, 'A', '批量通知', '', 0, 'schedule.waitlist/myWaitlistBatchNotify', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_waitlist_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.waitlist/myWaitlistBatchNotify');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_waitlist_id, 'A', '通知候补', '', 0, 'schedule.waitlist/myWaitlistNotify', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_waitlist_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.waitlist/myWaitlistNotify');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_waitlist_id, 'A', '候补转正', '', 0, 'schedule.waitlist/myWaitlistConvert', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_waitlist_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.waitlist/myWaitlistConvert');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_waitlist_id, 'A', '候补失效', '', 0, 'schedule.waitlist/myWaitlistInvalidate', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_waitlist_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.waitlist/myWaitlistInvalidate');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_waitlist_id, 'A', '候补统计', '', 0, 'schedule.waitlist/myWaitlistStatistics', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_waitlist_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'schedule.waitlist/myWaitlistStatistics');

-- 订单列表
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_order_id, 'A', '订单详情', '', 0, 'order.order/myOrderDetail', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_order_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'order.order/myOrderDetail');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_order_id, 'A', '开始服务', '', 0, 'order.order/myOrderStartService', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_order_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'order.order/myOrderStartService');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_order_id, 'A', '完成服务', '', 0, 'order.order/myOrderComplete', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_order_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'order.order/myOrderComplete');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_order_id, 'A', '订单统计', '', 0, 'order.order/myOrderStatistics', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_order_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'order.order/myOrderStatistics');

-- 动态管理
INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_dynamic_id, 'A', '动态详情', '', 0, 'dynamic.dynamic/myDynamicDetail', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_dynamic_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'dynamic.dynamic/myDynamicDetail');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_dynamic_id, 'A', '新增动态', '', 0, 'dynamic.dynamic/myDynamicAdd', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_dynamic_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'dynamic.dynamic/myDynamicAdd');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_dynamic_id, 'A', '编辑动态', '', 0, 'dynamic.dynamic/myDynamicEdit', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_dynamic_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'dynamic.dynamic/myDynamicEdit');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_dynamic_id, 'A', '删除动态', '', 0, 'dynamic.dynamic/myDynamicDelete', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_dynamic_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'dynamic.dynamic/myDynamicDelete');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_dynamic_id, 'A', '动态类型选项', '', 0, 'dynamic.dynamic/myDynamicTypeOptions', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_dynamic_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'dynamic.dynamic/myDynamicTypeOptions');

INSERT INTO `la_system_menu`(`pid`,`type`,`name`,`icon`,`sort`,`perms`,`paths`,`component`,`selected`,`params`,`is_cache`,`is_show`,`is_disable`,`create_time`,`update_time`)
SELECT @menu_dynamic_id, 'A', '动态状态选项', '', 0, 'dynamic.dynamic/myDynamicStatusOptions', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @menu_dynamic_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'dynamic.dynamic/myDynamicStatusOptions');

-- 4) 角色绑定：服务人员角色绑定新 C/A 菜单
SET @staff_role_id = (
    SELECT `id` FROM `la_system_role`
    WHERE `name` = '服务人员' AND `delete_time` IS NULL
    ORDER BY `id` DESC
    LIMIT 1
);

INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT @staff_role_id, m.`id`
FROM `la_system_menu` m
WHERE @staff_role_id IS NOT NULL
  AND (
      m.`id` = @staff_center_menu_id
      OR m.`pid` = @staff_center_menu_id
      OR m.`perms` IN (
          'staff.staff/myProfileUpdate',
          'staff.staff/myProfilePackageConfig',
          'staff.staff/myProfileUpdatePackageConfig',
          'staff.staff/myProfileCreatePackage',
          'staff.staff/myProfileUpdateStaffPackage',
          'staff.staff/myProfileDeletePackage',
          'staff.staff/myProfileBannerList',
          'staff.staff/myProfileBannerAdd',
          'staff.staff/myProfileBannerEdit',
          'staff.staff/myProfileBannerDelete',
          'staff.staff/myProfileBannerSort',
          'staff.staff/myProfileBannerConfig',
          'schedule.schedule/myCalendarSetStatus',
          'schedule.schedule/myCalendarBatchSet',
          'schedule.schedule/myCalendarUnlock',
          'schedule.schedule/myCalendarStatistics',
          'schedule.scheduleRule/myRuleDetail',
          'schedule.scheduleRule/myRuleSave',
          'schedule.scheduleRule/myRuleDelete',
          'schedule.scheduleRule/myRuleChangeStatus',
          'schedule.scheduleRule/myRuleTemplate',
          'schedule.booking/myBookingDetail',
          'schedule.booking/myBookingConfirm',
          'schedule.booking/myBookingCancel',
          'schedule.booking/myBookingStatistics',
          'schedule.waitlist/myWaitlistDetail',
          'schedule.waitlist/myWaitlistBatchNotify',
          'schedule.waitlist/myWaitlistNotify',
          'schedule.waitlist/myWaitlistConvert',
          'schedule.waitlist/myWaitlistInvalidate',
          'schedule.waitlist/myWaitlistStatistics',
          'order.order/myOrderDetail',
          'order.order/myOrderStartService',
          'order.order/myOrderComplete',
          'order.order/myOrderStatistics',
          'dynamic.dynamic/myDynamicDetail',
          'dynamic.dynamic/myDynamicAdd',
          'dynamic.dynamic/myDynamicEdit',
          'dynamic.dynamic/myDynamicDelete',
          'dynamic.dynamic/myDynamicTypeOptions',
          'dynamic.dynamic/myDynamicStatusOptions'
      )
  )
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_role_menu` rm
      WHERE rm.`role_id` = @staff_role_id AND rm.`menu_id` = m.`id`
  );

-- 5) 清理服务人员角色上的旧管理员动作权限绑定（仅 staff 角色）
DELETE rm
FROM `la_system_role_menu` rm
JOIN `la_system_menu` m ON m.`id` = rm.`menu_id`
WHERE rm.`role_id` = @staff_role_id
  AND m.`perms` IN (
      'staff.staff/lists',
      'staff.staff/detail',
      'staff.staff/add',
      'staff.staff/edit',
      'staff.staff/delete',
      'staff.staff/changeStatus',
      'staff.staff/resetAdminPassword',
      'staff.staff/getPackageConfig',
      'staff.staff/updatePackageConfig',
      'staff.staff/createStaffPackage',
      'staff.staff/updateStaffPackage',
      'staff.staff/deleteStaffPackage',
      'staff.staff/getBannerList',
      'staff.staff/addBanner',
      'staff.staff/editBanner',
      'staff.staff/deleteBanner',
      'staff.staff/sortBanner',
      'staff.staff/updateBannerConfig',
      'staff.staff/add:edit',
      'schedule.schedule/lists',
      'schedule.schedule/monthCalendar',
      'schedule.schedule/detail',
      'schedule.schedule/setStatus',
      'schedule.schedule/batchSet',
      'schedule.schedule/unlock',
      'schedule.schedule/statistics',
      'schedule.schedule/lockRecords',
      'schedule.scheduleRule/lists',
      'schedule.scheduleRule/detail',
      'schedule.scheduleRule/add',
      'schedule.scheduleRule/edit',
      'schedule.scheduleRule/delete',
      'schedule.scheduleRule/changeStatus',
      'schedule.scheduleRule/globalRule',
      'schedule.scheduleRule/staffRule',
      'schedule.booking/lists',
      'schedule.booking/detail',
      'schedule.booking/confirm',
      'schedule.booking/cancel',
      'schedule.booking/statistics',
      'schedule.waitlist/lists',
      'schedule.waitlist/detail',
      'schedule.waitlist/batchNotify',
      'schedule.waitlist/notify',
      'schedule.waitlist/convert',
      'schedule.waitlist/invalidate',
      'schedule.waitlist/statistics',
      'order.order/lists',
      'order.order/detail',
      'order.order/startService',
      'order.order/complete',
      'order.order/cancel',
      'order.order/auditVoucher',
      'order.order/logs',
      'order.order/addRemark',
      'order.order/statistics',
      'dynamic.dynamic/lists',
      'dynamic.dynamic/detail',
      'dynamic.dynamic/add',
      'dynamic.dynamic/edit',
      'dynamic.dynamic/audit',
      'dynamic.dynamic/offline',
      'dynamic.dynamic/setTop',
      'dynamic.dynamic/setHot',
      'dynamic.dynamic/delete',
      'dynamic.dynamic/typeOptions',
      'dynamic.dynamic/statusOptions',
      'dynamic.dynamic/commentLists',
      'dynamic.dynamic/deleteComment',
      'dynamic.dynamic/setCommentTop',
      'dynamic.dynamic/statistics'
  );

-- 6) 清理遗留的空白“服务管理”子菜单（component=service/index）
-- 说明：该菜单在前端无对应组件，访问时会出现空白页。
DELETE rm
FROM `la_system_role_menu` rm
JOIN `la_system_menu` m ON m.`id` = rm.`menu_id`
WHERE m.`type` = 'C'
  AND m.`paths` = 'service'
  AND m.`component` = 'service/index';

DELETE FROM `la_system_menu`
WHERE `type` = 'C'
  AND `paths` = 'service'
  AND `component` = 'service/index';

SET FOREIGN_KEY_CHECKS = 1;
