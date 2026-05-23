# shared/contracts

跨端接口与状态契约的轻量共享目录。

当前策略：

- `core.ts` 作为三端 TypeScript 类型的单一参考来源。
- 短期不强制 admin/uniapp/pc 直接 import，避免路径别名和构建配置引入额外风险。
- 新增/修改核心接口时，先更新 `docs/contracts/openapi-core.yaml`，再同步 `core.ts`。
- 中期可用脚本从 OpenAPI 生成类型，并替换三端复制的 `RequestCodeEnum` 和业务状态枚举。

迁移建议：

1. 先在 PC 或 admin 选择一个低风险 API 引入 `ApiEnvelope<T>`。
2. 验证构建路径别名和 tsconfig include。
3. 再逐步替换三端重复枚举。
