-- =====================================================
-- 订单转让入口下线（非破坏性迁移）
-- 创建时间：2026-03-17
-- 说明：
-- 1. 隐藏后台“订单转让”菜单与对应动作权限入口
-- 2. 保留历史数据、角色关系与表结构，仅下线新流程入口
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

UPDATE `la_system_menu`
SET `is_show` = 0,
    `is_disable` = 1,
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` IN ('C', 'A')
  AND (
      `perms` IN ('order.order_transfer/lists', 'ops.orderTransfer/lists')
      OR `perms` LIKE 'order.order_transfer/%'
      OR `perms` LIKE 'ops.orderTransfer/%'
      OR `component` = 'order/transfer/index'
      OR `paths` IN ('transfer', 'order-transfer')
  );

SET FOREIGN_KEY_CHECKS = 1;
