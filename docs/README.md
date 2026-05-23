# Wedding Management 工程治理文档索引

## 核心可靠性

- `contracts/api-response.md`：统一响应 Envelope、request_id、前端错误态约定。
- `contracts/openapi-core.yaml`：预约/订单/支付/档期/问卷核心 OpenAPI 契约子集。
- `architecture/core-state-machine.md`：订单、支付、档期、订单项、问卷状态流转约束。
- `architecture/module-boundaries.md`：模块边界、关键流程、低风险拆分路线。
- `qa/core-reliability-test-matrix.md`：并发抢档、重复支付回调、取消后回调、关键失败态测试矩阵。

## 运维与交付

- `ops/database-migration.md`：老库升级、回滚、SQL 迁移规范。
- `ops/ci-cd-quality-release.md`：CI/CD、质量门禁、灰度与回滚流程。
- `ops/deployment-environment.md`：PHP/Node/MySQL/Redis、Imagick 环境差异、静态资源发布路径。
- `ops/structured-logging.md`：request_id、用户/管理员 ID、订单 ID 的结构化日志上下文。

## 共享契约

- `../shared/contracts/core.ts`：跨端 TypeScript 状态和响应类型参考。
- `../server/app/common/contract/CoreStateContract.php`：后端状态流转辅助契约。
- `../server/app/common/service/RequestContextService.php`：request_id 与结构化日志上下文。
