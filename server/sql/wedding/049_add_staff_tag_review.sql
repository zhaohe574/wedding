-- =============================================
-- 服务人员标签审核功能
-- 创建日期: 2026-04-01
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 服务人员标签变更申请表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `la_staff_tag_apply` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '申请ID',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务人员ID',
  `current_tag_ids` text COMMENT '当前生效标签ID(JSON数组)',
  `apply_tag_ids` text COMMENT '申请标签ID(JSON数组)',
  `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源:1-uniapp,2-后台自助',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态:0-待审核,1-已通过,2-已拒绝',
  `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `submit_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '提交用户ID',
  `submit_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '提交后台账号ID',
  `audit_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
  `audit_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_staff_id` (`staff_id`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_source` (`source`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='服务人员标签变更申请表';

-- ----------------------------
-- 标签审核菜单
-- ----------------------------
SET @staff_parent_id = COALESCE(
    (
        SELECT `pid`
        FROM `la_system_menu`
        WHERE `perms` = 'ops.staff/lists'
        LIMIT 1
    ),
    (
        SELECT `pid`
        FROM `la_system_menu`
        WHERE `perms` = 'service.staff/lists'
        LIMIT 1
    ),
    (
        SELECT `pid`
        FROM `la_system_menu`
        WHERE `perms` = 'ops.styleTag/lists'
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
SELECT @staff_parent_id, 'C', '标签审核', '', 195, 'ops.staffTagReview/lists', 'staff-tag-review', 'staff/tag_review/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_parent_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffTagReview/lists'
);

SET @staff_tag_review_menu_id = (
    SELECT `id`
    FROM `la_system_menu`
    WHERE `perms` = 'ops.staffTagReview/lists'
    LIMIT 1
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_tag_review_menu_id, 'A', '详情', '', 10, 'ops.staffTagReview/detail', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_tag_review_menu_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffTagReview/detail'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_tag_review_menu_id, 'A', '审核通过', '', 20, 'ops.staffTagReview/approve', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_tag_review_menu_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffTagReview/approve'
);

INSERT INTO `la_system_menu`(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @staff_tag_review_menu_id, 'A', '审核拒绝', '', 30, 'ops.staffTagReview/reject', '', '', '', '', 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @staff_tag_review_menu_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'ops.staffTagReview/reject'
);

SET FOREIGN_KEY_CHECKS = 1;
