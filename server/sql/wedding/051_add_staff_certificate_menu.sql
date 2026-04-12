-- =============================================
-- 服务人员证书后台菜单
-- 创建日期: 2026-04-12
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

SET @staff_parent_id = COALESCE(
    (
        SELECT `pid`
        FROM `la_system_menu`
        WHERE `perms` = 'ops.staff/lists'
        LIMIT 1
    ),
    (
        SELECT `id`
        FROM `la_system_menu`
        WHERE `type` = 'M' AND `paths` = 'service'
        LIMIT 1
    ),
    (
        SELECT `id`
        FROM `la_system_menu`
        WHERE `type` = 'M' AND `name` = '服务管理'
        LIMIT 1
    )
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_parent_id, 'C', '人员证书', '', 185, 'ops.staffCertificate/lists', 'certificate', 'staff/certificate/index', '/staff', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_parent_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffCertificate/lists'
);

SET @staff_certificate_menu_id = (
    SELECT `id`
    FROM `la_system_menu`
    WHERE `perms` = 'ops.staffCertificate/lists'
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_certificate_menu_id, 'A', '详情', '', 10, 'ops.staffCertificate/detail', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_certificate_menu_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffCertificate/detail'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_certificate_menu_id, 'A', '新增', '', 20, 'ops.staffCertificate/add', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_certificate_menu_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffCertificate/add'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_certificate_menu_id, 'A', '编辑', '', 30, 'ops.staffCertificate/edit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_certificate_menu_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffCertificate/edit'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_certificate_menu_id, 'A', '删除', '', 40, 'ops.staffCertificate/delete', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_certificate_menu_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffCertificate/delete'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_certificate_menu_id, 'A', '审核', '', 50, 'ops.staffCertificate/audit', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_certificate_menu_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffCertificate/audit'
);

SET FOREIGN_KEY_CHECKS = 1;
