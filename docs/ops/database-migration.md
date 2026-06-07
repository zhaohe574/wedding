# 数据库迁移、老库升级与回滚规范

适用：`server/public/install/db/like.sql` 全量安装库、`server/sql/**/update.sql` 历史升级脚本、后续业务增量 SQL。

## 1. 文件组织

- 全量新装：只维护 `server/public/install/db/like.sql`。
- 老库升级：每个业务增量必须有独立 SQL 文件，命名建议：
  - `server/sql/YYYYMMDD_<domain>_<change>.sql`
  - 如变更可回滚，配套 `server/sql/YYYYMMDD_<domain>_<change>.rollback.sql`
- 模板：`server/sql/_template/migration.sql`、`server/sql/_template/rollback.sql`。

## 2. 必填头部

每个迁移文件头部必须包含：

```sql
-- Migration: 20260523_core_reliability
-- Owner: backend/architect
-- Scope: table/index/data
-- Precheck: 描述上线前检查
-- Rollback: 对应 rollback 文件或“不可自动回滚，需备份恢复”
```

## 3. 执行顺序

1. 备份目标库：结构 + 数据，记录备份文件路径和校验摘要。
2. 在预发库执行迁移；执行后运行冒烟：登录、订单详情、锁档、支付状态、问卷列表。
3. 低峰期生产执行；大表 DDL 优先使用在线变更工具或拆分窗口。
4. 执行后记录：执行人、时间、SQL 文件、影响行数、异常与回滚决策。

## 4. 幂等写法要求

MySQL 版本差异较大时，优先采用“预检查 + 可重复执行”的写法：

- 表：`CREATE TABLE IF NOT EXISTS`。
- 数据：用唯一键 + `INSERT ... ON DUPLICATE KEY UPDATE`，或先 `DELETE` 再插入固定 ID 数据。
- 字段/索引：若 MySQL 不支持 `ADD COLUMN IF NOT EXISTS`，执行前必须人工查询 `information_schema`，并在 SQL 注释中写明检查语句。

## 5. 回滚要求

- 纯新增表：可 `DROP TABLE IF EXISTS`，但仅允许在确认没有线上有效数据时执行。
- 新增字段：默认不自动 drop，除非字段未被业务写入；否则回滚应用代码即可。
- 菜单/权限数据：回滚脚本应只删除本次新增的 `perms`/路径，不影响历史角色。
- 支付/订单/档期数据：禁止通过回滚 SQL 修改状态；必须走补偿单或人工审批脚本。

## 6. 当前老库缺口

全量安装 SQL 已包含新人问卷表结构，但现有 `server/sql/20260513_couple_questionnaire_admin_menu.sql` 主要覆盖菜单权限。老库如果早于问卷功能，需要先执行：

- `server/sql/20260523_couple_questionnaire_schema.sql`：补 `la_couple_question_bank`、`la_couple_questionnaire`、`la_couple_questionnaire_version`、`la_couple_questionnaire_task`、`la_couple_questionnaire_answer` 表及初始化题库。
- `server/sql/20260523_couple_questionnaire_delivery_patch.sql`：若库中已存在旧版问卷表，补齐任务发送失败追踪、查看时间、过期时间与重试索引。
- `server/sql/20260513_couple_questionnaire_admin_menu.sql`：补后台菜单权限。

执行前检查：

```sql
SHOW TABLES LIKE 'la_couple_questionnaire%';
SHOW TABLES LIKE 'la_couple_question_bank';
```

## 7. request_id 可选字段化

本次代码先把 `request_id` 写入响应体/响应头/操作日志 `params.__context`，无需迁移。若后续要字段化操作日志，可新增：

```sql
ALTER TABLE la_operation_log ADD COLUMN request_id VARCHAR(64) NOT NULL DEFAULT '' COMMENT '请求ID' AFTER id;
CREATE INDEX idx_operation_log_request_id ON la_operation_log(request_id);
```

上线条件：确认所有写 `la_operation_log` 的模型均允许该字段或显式赋值，避免老库未迁移时失败。
