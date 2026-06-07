# CLAUDE.md

## 项目概览

- 项目名：`wedding-management`，一个婚庆服务预约与管理平台。
- 业务目标：支撑婚庆服务展示、客户预约、员工/档期管理、订单与售后、财务结算、CRM 跟进、评价动态和消息通知等流程。
- 项目形态：一个 ThinkPHP 后端加三个前端子项目，分别服务管理后台、PC 官网/用户站和移动端/H5/小程序。
- 发布方式：前端构建产物会复制到 `server/public/admin`、`server/public/pc`、`server/public/mobile`，由后端 public 目录统一承载。
- 来源提示：部分 README 仍是脚手架模板，实际信息以源码、`package.json`、lock 文件和 `CHANGELOG.md` 为准。

## 技术栈摘要

- 后端：PHP >= 8.0、ThinkPHP 8、ThinkORM、ThinkPHP multi-app/view、Composer、MySQL、Redis 缓存/分布式锁。
- 管理后台 `admin`：Vue 3、Vite、TypeScript、Element Plus、Pinia、Vue Router、Axios、Tailwind CSS、ESLint、Prettier。
- PC 端 `pc`：Nuxt 3、Vue 3、Pinia、Element Plus、Tailwind CSS，支持静态生成与 SSR 构建。
- 移动端 `uniapp`：uni-app Vue3、Vite、TypeScript、Pinia、Tuniao UI，支持 H5、微信小程序、App 等目标。
- 第三方集成：微信/公众号/小程序、EasyWechat、短信、七牛云、腾讯 COS、阿里云 OSS、支付宝。

## 目录结构

```text
.
├── admin/        Vue/Vite 管理后台，构建后复制到 server/public/admin
├── pc/           Nuxt PC 端，构建后复制到 server/public/pc
├── uniapp/       uni-app 移动端/H5/小程序，H5 构建后复制到 server/public/mobile
├── server/       ThinkPHP 后端、API、多应用、公共业务代码和 public 入口
├── release/      发布包输出目录
├── .claude/      本项目本地 Claude 计划/上下文
├── .omx/         本地任务、计划、报告状态，不作为业务运行依赖
└── CHANGELOG.md  近期中文变更记录
```

### 后端关键结构

- `server/app/adminapi`：管理后台 API，含 `controller`、`logic`、`lists`、`validate` 等层。
- `server/app/api`：用户端/移动端 API。
- `server/app/common`：共享模型、枚举、服务、缓存、支付、短信、存储、微信等能力。
- `server/config`：应用、数据库、缓存、路由、文件系统、项目参数配置。
- `server/route/app.php`：前端入口转发和 `crontab` HTTP 触发路由。
- `server/sql`：版本化 SQL 增量脚本。

### 前端关键结构

- `admin/src/views`：后台页面，覆盖 CRM、订单、财务、员工、档期、服务、设置等模块。
- `admin/src/api`：后台接口封装；`admin/src/router`：菜单驱动动态路由。
- `pc/pages`：Nuxt 页面路由；`pc/api`、`pc/stores`、`pc/components` 承载接口、状态和组件。
- `uniapp/src/pages`：移动端主包页面；`uniapp/src/packages`：分包页面和组件。
- `uniapp/src/pages.json`、`uniapp/src/manifest.json`：uni-app 页面、分包和平台配置入口。

## 常用命令

### 根目录质量入口

- 静态合同检查：`npm run qa:contracts`
- 全量 QA 别名：`npm run qa:all`
- 当前根级 QA 会串行执行核心可靠性、问卷全链路和三端治理合同检查，不依赖数据库、Composer 或前端构建。

### 后端 `server`

- 安装依赖：`cd server && composer install`
- 本地运行：`cd server && php think run`
- 定时任务：`cd server && php think crontab`，或访问 `/crontab?secret=...`，密钥来自 `project.crontab_secret`。
- 依赖刷新：`cd server && composer dump-autoload`

### 管理后台 `admin`

- 安装依赖：`cd admin && npm install`
- 开发：`cd admin && npm run dev`
- 构建发布：`cd admin && npm run build`
- 类型检查：`cd admin && npm run type-check` 或 `npm run type-check:active`
- Lint：`cd admin && npm run lint`
- 预览：`cd admin && npm run preview`

### PC 端 `pc`

- 安装依赖：`cd pc && npm install`
- 开发：`cd pc && npm run dev`
- 静态构建发布：`cd pc && npm run build`
- SSR 构建发布：`cd pc && npm run build:ssr`
- 预览/启动：`cd pc && npm run preview` 或 `npm run start`

### 移动端 `uniapp`

- 安装依赖：`cd uniapp && npm install`
- 交互式开发选择：`cd uniapp && npm run dev`
- H5 开发：`cd uniapp && npm run dev:h5`
- 微信小程序开发：`cd uniapp && npm run dev:mp-weixin`
- 交互式发布选择：`cd uniapp && npm run build`
- H5 构建发布：`cd uniapp && npm run build:h5`
- 微信小程序构建并检查体积：`cd uniapp && npm run build:mp-weixin:check`
- 配置/迁移校验：`cd uniapp && npm run validate:all`

## 编码约定与模式

- 格式化：4 空格缩进、单引号、无分号、无尾逗号；`admin/uniapp` printWidth 100，`pc` printWidth 80。
- Vue/TypeScript：普遍使用 `@` 指向 `src`，请求、枚举、hooks、stores、utils 分层明显。
- 后台路由：`admin/src/router/index.ts` 使用 `import.meta.glob('/src/views/**/*.vue')` 动态映射后端菜单。
- 请求封装：前端请求层会自动附加 `token`、`version`，并统一处理 `code/data/msg/show` 响应结构。
- 后端分层：常见链路为 Controller -> Logic -> Lists -> Model/Service -> Validate。
- 后端命名：控制器、逻辑、验证器、模型使用明确后缀；业务服务集中在 `app/common/service`。
- 数据库：MySQL 表前缀默认为 `la_`，自动时间字段为 `create_time/update_time`，软删除常见字段为 `delete_time`。
- 关键业务：档期锁定、订单变更、退款、确认函等流程依赖事务、服务类和 Redis 锁，不要绕过现有 Logic/Service。

## 重要文件

- `server/composer.json`、`server/composer.lock`：后端依赖与精确锁定版本。
- `server/.env`：本地/真实环境变量，可能包含敏感信息。
- `server/config/database.php`：数据库连接、表前缀和时间字段配置。
- `server/config/cache.php`：文件/Redis 缓存配置。
- `server/config/project.php`：项目级配置入口。
- `server/route/app.php`：管理后台、移动端、PC 端入口转发和定时任务路由。
- `server/app/common/service/RedisLockService.php`：档期等并发敏感场景的 Redis 锁能力。
- `server/app/common/service/OrderConfirmLetterService.php`、`server/app/common/service/OrderConfirmLetterRenderer.php`：订单确认函生成核心。
- `server/app/common/service/ConfigService.php`：配置读写与缓存失效逻辑。
- `admin/src/utils/request/index.ts`、`uniapp/src/utils/request/index.ts`：前端请求拦截和响应转换。
- `admin/src/permission.ts`：管理后台路由守卫/权限初始化入口。
- `pc/nuxt.config.ts`、`pc/nuxt/env.ts`：PC 端运行时配置、baseURL 和 SSR 开关。
- `admin/scripts/release.mjs`、`pc/scripts/build.mjs`、`uniapp/scripts/release.mjs`：构建后复制到后端 public 的发布脚本。
- `CHANGELOG.md`：近期业务变更和功能线索。

## 特殊注意事项

- 不存在统一 monorepo workspace；四个子项目依赖和命令需要分别在各自目录执行。
- 已有根级静态合同检查入口；变更后优先运行 `npm run qa:contracts`，再按影响范围运行相关 type-check、lint、validate，并做关键流程手工验证。
- Admin 核心业务页应使用 `admin-page-shell`，筛选区放入 `#search`，统计区放入 `#stats`，主体表格区域使用 `admin-page-section`。
- PC 端当前定位为企业展示页，只补展示、案例和联系转化，不在 PC 端新增预约、订单、支付链路。
- 移动端 UI 治理优先沿用 `PageShell`、`BaseNavbar`、`BaseCard`、`BaseButton`、`EmptyState` 和自定义 tabbar，不从零另起一套组件。
- 移动端新增提示/确认交互时优先使用 `uniapp/src/utils/feedback.ts`，不要继续在新页面里直接散落 `uni.showToast` / `uni.showModal`。
- CRM 模块按客户、跟进、顾问、流失预警四条链路推进，沿用现有 `admin/src/api/crm/*` 和 `server/app/adminapi/*/crm/*` 分层。
- `server/README.md` 仍写 ThinkPHP 6 模板信息，但 `composer.lock` 显示当前框架为 ThinkPHP 8。
- 前端发布脚本会删除并重建 `server/public/admin`、`server/public/pc`、`server/public/mobile`，执行前确认目标目录无手工文件。
- `pc` 的 SSR 构建由环境变量 `NUXT_SSR` 影响，静态和 SSR 发布内容不同。
- `uniapp` 的 `scripts/develop.js`、`scripts/publish.js` 有交互选择；CI 中建议直接调用具体脚本。
- `.env` 文件已存在于多个子项目，提交、打包或共享前需要排除或脱敏。
- 避免编辑生成/第三方/运行态目录：`node_modules`、`server/vendor`、`server/runtime`、`admin/dist`、`pc/.nuxt`、`pc/.output`、`uniapp/dist`。
