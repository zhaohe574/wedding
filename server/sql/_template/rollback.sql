-- Rollback: YYYYMMDD_domain_change
-- Owner: backend/architect
-- Preconditions:
--   1. 已确认本次变更无生产有效数据依赖，或已完成业务补偿。
--   2. 已备份当前库。
-- Notes:
--   - 回滚菜单/配置数据时只删除本次新增项。
--   - 禁止直接 UPDATE 支付、订单、档期状态；必须走补偿流程。

START TRANSACTION;

-- 在这里写可安全回滚的数据变更。

COMMIT;
