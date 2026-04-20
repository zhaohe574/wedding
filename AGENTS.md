# Repository Guidelines

## Project Structure & Module Organization
- 根目录按端划分：`server/`（ThinkPHP 后端）、`admin/`（Vue3 管理后台）、`uniapp/`（小程序/H5）、`pc/`（Nuxt3 前台）、`docs/`（设计与调试产物）。
- 后端核心代码在 `server/app/`，其中 `adminapi/` 与 `api/` 负责接口，`common/` 放模型、枚举与通用能力。
- 管理后台以 `admin/src/views`（页面）、`admin/src/api`（请求）、`admin/src/components`（复用组件）组织。
- 移动端页面在 `uniapp/src/pages`，PC 页面在 `pc/pages`；共享逻辑优先沉淀到各自 `utils/` 或 `stores/`。

## Build, Test, and Development Commands
- 后端依赖安装：`cd server && composer install`。
- 管理后台开发/构建：`cd admin && npm run dev`、`npm run build`。
- 小程序开发/构建：`cd uniapp && npm run dev:mp-weixin`、`npm run build:h5`。
- PC 前台开发/构建：`cd pc && npm run dev`、`npm run build`。
- 代码质量检查：`cd admin && npm run lint && npm run type-check`；`cd uniapp && npm run lint && npm run validate:all`。

## Coding Style & Naming Conventions
- 前端遵循 ESLint + Prettier：4 空格缩进、单引号、无分号；提交前先执行 lint。
- Vue 组件优先使用 `<script setup lang="ts">`；组件名用 PascalCase，文件名用 kebab-case。
- 后端遵循 `Controller -> Logic -> Service -> Model`，控制器仅处理参数校验与响应。
- 避免魔法值，优先复用 `server/app/common/enum`、公共 Service 与已有请求封装。

## Testing Guidelines
- 当前仓库未统一单元测试框架，默认以 lint、type-check、build 作为最小验证集。
- 每次改动至少提供一条可复现的验证步骤；涉及 UI 的变更需附关键截图或录屏。
- 回归覆盖移动端与桌面端关键链路（登录、预约下单、支付后订单状态流转）。
- 建议在 PR 中附上 `npm run lint`、`npm run build`、`composer install` 的关键输出摘要。

## Commit & Pull Request Guidelines
- 提交信息沿用 Conventional Commits：`feat:`、`fix:`、`refactor:`、`chore:`。
- 推荐格式：`type(scope): 简短中文说明`，单次提交只解决一个主题。
- PR 需包含：变更背景、影响目录、验证命令结果、界面截图（如有）与关联 issue。
- 禁止提交 `node_modules/`、`vendor/`、`.env`、密钥与其他本地敏感文件。

## Security & Configuration Tips
- 使用 `server/.example.env` 初始化本地 `.env`，先完成 MySQL/Redis 配置再启动服务。
- 前端环境变量（如 `VITE_APP_BASE_URL`）必须与目标后端环境一致，避免跨环境误调。
- 网络调试产物统一放在 `docs/har/`，归档前务必脱敏（Token、手机号、密钥）。

## Architecture & Collaboration Notes
- 业务改动应优先保持现有分层边界：接口层不写复杂业务，业务逻辑优先落在 `Logic` 或 `Service`。
- 涉及多端联动（`server`、`admin`、`uniapp`、`pc`）时，先定义接口字段，再同步更新调用端，避免并行猜测。
- 新增配置项时同步更新文档与默认值来源，确保开发、测试、生产环境行为一致。
- 大范围改动请拆分为可评审的小 PR，先合并基础重构，再合并功能变更，降低回归风险。


## other
- 完整的项目安装数据库为server/sql/2.0.0.20260201/update.sql
- 分步实施的数据库目录为server/sql/wedding ，此目录下的sql文件只为了在本地测试时使用。
- 数据库相关调整默认直接修改server/sql/2.0.0.20260201/update.sql中的建表语句、索引定义与初始化数据。
- 未经彦祖明确要求，不新增server/sql/wedding下的数据库变更脚本；需要改库时优先改主安装SQL，而不是补迁移脚本。
- 若彦祖明确要求用于本地数据库验证，可同步在server/sql/wedding下补充对应迁移脚本，并保持与主安装SQL一致。
