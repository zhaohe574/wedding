-- =============================================
-- 精简版后台菜单清理 + 评价奖励字段补充
-- 创建日期: 2026-03-12
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 用户积分字段
-- ----------------------------
SET @add_user_points = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'la_user'
              AND COLUMN_NAME = 'user_points'
        ),
        'SELECT 1',
        'ALTER TABLE `la_user` ADD COLUMN `user_points` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''用户积分'' AFTER `user_money`'
    )
);
PREPARE stmt FROM @add_user_points;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 评价奖励发放幂等字段
-- ----------------------------
SET @add_review_reward_grant_time = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'la_review'
              AND COLUMN_NAME = 'reward_grant_time'
        ),
        'SELECT 1',
        'ALTER TABLE `la_review` ADD COLUMN `reward_grant_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''评价奖励发放时间'' AFTER `reward_points`'
    )
);
PREPARE stmt FROM @add_review_reward_grant_time;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_share_reward_grant_time = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'la_review_share_reward'
              AND COLUMN_NAME = 'reward_grant_time'
        ),
        'SELECT 1',
        'ALTER TABLE `la_review_share_reward` ADD COLUMN `reward_grant_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''晒单奖励发放时间'' AFTER `reward_points`'
    )
);
PREPARE stmt FROM @add_share_reward_grant_time;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_share_reward_audit_remark = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'la_review_share_reward'
              AND COLUMN_NAME = 'audit_remark'
        ),
        'SELECT 1',
        'ALTER TABLE `la_review_share_reward` ADD COLUMN `audit_remark` varchar(255) NOT NULL DEFAULT '''' COMMENT ''审核备注'' AFTER `audit_time`'
    )
);
PREPARE stmt FROM @add_share_reward_audit_remark;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 禁用已下线或空壳菜单
-- ----------------------------
UPDATE `la_system_menu`
SET `is_disable` = 1,
    `is_show` = 0,
    `update_time` = UNIX_TIMESTAMP()
WHERE `paths` IN ('crm', 'timeline', 'aftersale')
   OR `component` IN (
        'crm/customer/index',
        'crm/warning/index',
        'aftersale/ticket/index',
        'timeline/lists/index',
        'financial/cost/index',
        'financial/invoice/index',
        'schedule/booking/index',
        'schedule/event/index'
   )
   OR `perms` LIKE 'crm.customer/%'
   OR `perms` LIKE 'crm.sales_advisor/%'
   OR `perms` LIKE 'crm.salesAdvisor/%'
   OR `perms` LIKE 'crm.customer_loss_warning/%'
   OR `perms` LIKE 'crm.customerLossWarning/%'
   OR `perms` LIKE 'crm.followRecord/%'
   OR `perms` LIKE 'growth.customer/%'
   OR `perms` LIKE 'growth.advisor/%'
   OR `perms` LIKE 'growth.lossWarning/%'
   OR `perms` LIKE 'growth.followRecord/%'
   OR `perms` LIKE 'timeline.timeline/%'
   OR `perms` LIKE 'growth.timeline/%'
   OR `perms` LIKE 'aftersale.aftersale/%'
   OR `perms` LIKE 'ops.aftersaleTicket/%'
   OR `perms` LIKE 'financial.cost/%'
   OR `perms` LIKE 'finance.cost/%'
   OR `perms` LIKE 'financial.invoice/%'
   OR `perms` LIKE 'finance.invoice/%'
   OR `perms` LIKE 'schedule.calendarEvent/%'
   OR `perms` LIKE 'ops.calendarEvent/%'
   OR `perms` LIKE 'schedule.booking/%'
   OR `perms` LIKE 'ops.booking/%';

-- ----------------------------
-- 新增晒单奖励菜单
-- ----------------------------
SET @review_menu_id = (
    SELECT `id`
    FROM `la_system_menu`
    WHERE `paths` = 'review'
      AND `type` = 'M'
      AND `delete_time` IS NULL
    ORDER BY `id` DESC
    LIMIT 1
);

INSERT INTO `la_system_menu`(
    `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`,
    `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`
)
SELECT @review_menu_id, 'C', '晒单奖励', '', 60, 'growth.reviewShareReward/lists', 'share-reward',
       'review/share_reward/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE @review_menu_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `la_system_menu`
      WHERE `perms` = 'growth.reviewShareReward/lists'
        AND `delete_time` IS NULL
  );

SET FOREIGN_KEY_CHECKS = 1;
