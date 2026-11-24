# shared/contracts

跨端接口与状态契约的轻量共享目录。

当前策略：

- `core.ts`、`dto.ts` 与 `generated/` 作为 `admin`、`newuniapp`（及必要时的 `pc`）TypeScript 类型的单一参考来源；旧包 `uniapp/` 已冻结。
- `generated/status.ts`：由后端状态枚举同步生成，校验命令 `node scripts/contracts/export-status.mjs --check`。
- `generated/endpoints.json`：由后端路由/控制器同步生成，生成与校验命令 `npm run endpoints:gen` / `node scripts/contracts/export-endpoints.mjs --check`。
- 修改状态或金额字段时必须同步契约脚本（`npm run contracts:gen` / `npm run endpoints:gen`）与回归断言。
- 新增/修改核心接口时，先更新 `docs/contracts/openapi-core.yaml`，再同步 `core.ts` 与 `dto.ts`。
