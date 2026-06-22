# 数据库迁移规范

核心业务表结构变更必须提供安装 SQL 与升级 SQL，并在上线前确认旧库数据兼容性。

当前安全与支付修复迁移文件：`server/sql/1.10.1.20260622/security_payment_permission.sql`。

活动报名、档期锁等新增迁移需要同步更新 `server/public/install/db/like.sql`，并提供幂等或上线前清理说明。
