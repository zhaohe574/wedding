# shared/contracts

跨端接口与状态契约的轻量共享目录。

当前策略：

- `core.ts` 作为三端 TypeScript 类型的单一参考来源。
- admin 与 uniapp 的订单接口已直接 import 核心付款类型，PC 保持展示端最小依赖。
- 修改状态或金额字段时必须同步接口文档与回归断言。
- 新增/修改核心接口时，先更新 `docs/contracts/openapi-core.yaml`，再同步 `core.ts`。
