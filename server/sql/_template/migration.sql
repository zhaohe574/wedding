-- Migration: YYYYMMDD_domain_change
-- Owner: backend/architect
-- Scope: table/index/data
-- Precheck:
--   1. SELECT VERSION();
--   2. SHOW TABLES LIKE 'target_table';
-- Rollback: server/sql/YYYYMMDD_domain_change.rollback.sql 或注明不可自动回滚
-- Notes:
--   - DDL 上线前必须备份；大表变更需评估锁表时间。
--   - 支付/订单/档期状态数据禁止通过迁移脚本直接修正。

START TRANSACTION;

-- 在这里写可重复执行的数据变更；DDL 是否支持事务取决于 MySQL 版本和语句类型。

COMMIT;
