-- =============================================
-- 管理后台：新增“作品管理”顶级菜单与权限
-- 目的：管理员审核服务人员作品并管理展示状态
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 清理旧位置：服务人员中心下的作品管理
SET @staff_center_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'staff_center' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

SET @old_staff_work_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `pid` = @staff_center_menu_id AND `paths` = 'work' AND `type` = 'C'
    ORDER BY `id` DESC
    LIMIT 1
);

DELETE rm
FROM `la_system_role_menu` rm
JOIN `la_system_menu` m ON rm.`menu_id` = m.`id`
WHERE m.`pid` = @old_staff_work_menu_id;

DELETE FROM `la_system_role_menu`
WHERE `menu_id` = @old_staff_work_menu_id;

DELETE FROM `la_system_menu`
WHERE `pid` = @old_staff_work_menu_id;

DELETE FROM `la_system_menu`
WHERE `id` = @old_staff_work_menu_id;

-- 顶级菜单：作品管理
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 0, 'M', '作品管理', 'el-icon-Picture', 730, '', 'staff_work', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `paths` = 'staff_work' AND `type` = 'M'
);

SET @staff_work_menu_id = (
    SELECT `id` FROM `la_system_menu`
    WHERE `paths` = 'staff_work' AND `type` = 'M'
    ORDER BY `id` DESC
    LIMIT 1
);

-- 菜单：作品列表
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_work_menu_id, 'C', '作品列表', '', 100, 'staff.staffWork/lists', 'lists', 'staff/work/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_work_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `pid` = @staff_work_menu_id AND `paths` = 'lists'
  );

-- 按钮权限：详情、审核、状态、封面、删除
INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '详情', '', 0, 'staff.staffWork/detail', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'staff.staffWork/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'staff.staffWork/detail')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '审核', '', 0, 'staff.staffWork/audit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'staff.staffWork/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'staff.staffWork/audit')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '修改状态', '', 0, 'staff.staffWork/changeStatus', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'staff.staffWork/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'staff.staffWork/changeStatus')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '设为封面', '', 0, 'staff.staffWork/setCover', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'staff.staffWork/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'staff.staffWork/setCover')
LIMIT 1;

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
`params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT id, 'A', '删除', '', 0, 'staff.staffWork/delete', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu`
WHERE `perms` = 'staff.staffWork/lists'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m2 WHERE m2.`perms` = 'staff.staffWork/delete')
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
