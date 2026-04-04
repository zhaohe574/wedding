# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概览

这是一个婚庆服务预约与管理平台，仓库包含四个主要应用：

- `server/`：ThinkPHP 8 多应用后端，承载管理后台 API、C 端 API，以及静态资源发布目录。
- `admin/`：Vue 3 + Vite + TypeScript 管理后台。
- `pc/`：Nuxt 3 官网/PC 端，构建后发布到后端静态目录。
- `uniapp/`：uni-app Vue 3 移动端，覆盖微信小程序、H5、App。

没有统一的根级构建入口；开发时通常进入对应子项目执行命令。

## 常用命令

### 后端 `server/`

```bash
composer install
php think run
php think service:discover
```

说明：
- `composer install` 会执行 `post-autoload-dump`，自动触发 `php think service:discover` 和 `php think vendor:publish`。
- 数据库变更不是通过迁移命令执行，而是手动执行 `server/sql/` 下的 SQL。

常看 SQL：

```bash
# 完整基线
server/sql/2.0.0.20260201/update.sql

# 婚庆业务增量脚本
server/sql/wedding/*.sql
```

### 管理后台 `admin/`

```bash
npm install
npm run dev
npm run type-check
npm run lint
npm run build
```

说明：
- `npm run build` 会先执行 Vite 构建，再通过 `admin/scripts/release.mjs` 覆盖复制到 `server/public/admin/`。

### PC 端 `pc/`

```bash
npm install
npm run dev
npm run build
npm run build:ssr
npm run preview
```

说明：
- `npm run build`：静态生成，并复制到 `server/public/pc/`。
- `npm run build:ssr`：SSR 构建，并按 `pc/scripts/build.mjs` 的过滤规则复制产物到 `server/public/pc/`。

### 移动端 `uniapp/`

```bash
npm install
npm run dev:h5
npm run dev:mp-weixin
npm run dev:app
npm run build:h5
npm run build:mp-weixin
npm run lint
npm run type-check:active
npm run validate:all
```

说明：
- `npm run dev` / `npm run build` 是交互式脚本，会提示选择微信小程序或 H5；自动化操作时优先使用显式命令如 `dev:h5`、`build:mp-weixin`。
- `npm run build:h5` 会把 H5 产物复制到 `server/public/mobile/`。
- `uniapp/package.json` 要求 Node `>=18.19.0`。

## 测试与校验

当前仓库没有发现已配置好的 PHPUnit、Vitest、Jest、Playwright 或 Cypress 项目级测试入口。

可用的主要校验方式：

```bash
# admin
npm run type-check
npm run lint

# uniapp
npm run type-check:active
npm run lint
npm run validate:all
```

如果用户要求“跑单测”或“跑某个测试”，先确认目标子项目是否临时新增了测试配置；按当前仓库状态，默认没有单测命令可直接运行。

## 高层架构

### 1. 后端是 ThinkPHP 多应用结构

`server/app/` 主要分为：

- `adminapi/`：管理后台 API。
- `api/`：小程序/H5/移动端 API，也包含部分 PC 端接口。
- `common/`：跨应用共享的模型、服务、基础控制器、逻辑层、列表层。
- `index/`：极小的默认入口应用，不是主业务承载层。

认证信息通过中间件注入 request：
- 管理端中间件写入 `request->adminInfo`，基类控制器再提取为 `$this->adminId`、`$this->adminInfo`。
- C 端中间件写入 `request->userInfo`，基类控制器再提取为 `$this->userId`、`$this->userInfo`。

### 2. 后端遵循稳定的分层模式

常见调用链是：

- `controller/`：只处理请求参数、调用逻辑层、返回响应。
- `logic/`：业务编排层，负责流程控制。
- `model/`：ORM 模型与核心领域行为。
- `service/`：可复用的跨场景服务。
- `lists/`：列表查询、筛选、分页、导出。
- `validate/`：参数校验规则。

新增接口或修复业务时，优先延续这个分层，不要把复杂业务直接塞进控制器。

### 3. 管理后台的菜单和路由由后端驱动

`admin` 不是写死本地路由表的后台。登录后：

- `admin/src/stores/modules/user.ts` 从后端拿到 `menu`。
- `admin/src/router/index.ts` 的 `filterAsyncRoutes()` 把后端菜单转换成前端路由。
- 页面组件通过 `import.meta.glob('/src/views/**/*.vue')` 动态加载。

这里还有一层“权限别名兼容”映射：旧权限前缀与新业务分组前缀会在路由工具里互相展开，因此菜单、权限码、页面组件路径经常需要联动排查，而不是只改一处。

### 4. 婚庆业务核心围绕“服务人员 + 套餐 + 区域 + 排期 + 订单变更”展开

重要领域关系：

- `Staff`：服务人员主体，如摄影、督导、管家等。
- `ServicePackage`：人员下挂套餐。
- `ServiceAddon`：预约附加项/附加服务。
- `Schedule`：档期占用与可预约判断。
- `Order` / `OrderItem`：订单与订单项。
- `OrderChange`：改期、加项、附加服务变更等订单变更流程。

几个跨多文件才容易看清的关键点：

#### 区域价格是当前价格体系的重要组成部分

价格不再只是套餐固定价。`PackageRegionPriceService`、`RegionDataService`、`StaffPriceService`、`OrderLogic`、`StaffLogic`、`BookingFlowService` 共同实现“按省/市/区解析套餐价格”的流程。

这意味着：
- 列表展示价、详情价、下单价不一定直接取 `service_package.price`。
- 只要请求里带区域上下文，就要警惕是否需要走区域价解析。
- 员工中心和管理后台编辑套餐时，也会同步维护区域价格表，而不是只改主表字段。

#### 预约流程是统一编排出来的

`BookingFlowService` 负责：
- 查询某服务人员可预约的附加项。
- 根据服务分类推导婚礼管家/婚礼督导等角色候选人。
- 按区域与日期过滤候选套餐与档期状态。

所以“预约页看什么”、“下单前可选什么”、“角色候选人如何出现”通常不在单个 controller 内，而在统一服务里收口。

#### 排期并发控制依赖 Redis 锁

`RedisLockService` 为档期占用提供分布式锁，封装了：
- 锁获取与重试。
- Lua 安全释放。
- 续期。
- 批量锁定。

凡是涉及预约、改期、批量占档，排查问题时都要同时看业务逻辑与锁调用，不要只看数据库写入。

#### 订单变更是强领域模型，不只是普通 CRUD

`app/common/model/order/OrderChange.php` 内部维护多种变更类型与状态：
- 改期
- 换人（当前部分端口已下线/停用）
- 加项
- 附加服务变更

很多真正的校验、状态迁移、价格差额、执行逻辑在模型和 logic 中共同完成，而不只是控制器里转发。

### 5. 员工后台权限不是单纯 RBAC，还叠加了 staff scope

`StaffService::getStaffScopeId()` 会把某些后台账号限制到唯一 staff 档案。

这会影响：
- 员工中心。
- 员工作品、档期、证书等个人资源维护。
- 一部分管理端列表或详情是否只能访问本人数据。

看到“后台账号无权限”或“明明登录了却看不到自己的内容”时，优先检查是否命中了 staff scope，而不是只看角色菜单。

### 6. 手机号字段存在“脱敏展示”和“完整值”两套口径

`Staff` 模型默认会把手机号按 `138****5678` 形式脱敏显示；编辑、回填、订单确认等场景则依赖 `mobile_full` 或原始字段。

因此：
- 做员工资料编辑时，不要直接拿 `toArray()` 里的 `mobile` 当完整手机号。
- API 返回给前端前，要先确认该场景应该返回脱敏值还是完整值。

## 前后端协作要点

- `admin`、`pc`、`uniapp` 都不是独立部署为主的目录；构建脚本默认把产物复制回 `server/public/` 下对应目录。
- `uniapp/src/api/`、`admin/src/api/`、`pc` 中的接口改动通常要与 `server/app/api` 或 `server/app/adminapi` 同步看。
- 仓库存在较多业务性“兼容映射”与“模块下线后保留占位响应”的情况，改接口前先确认当前功能是有效功能、兼容入口，还是已被显式下线。

## 初始化或排障时优先查看的位置

- 后端入口与基类：`server/app/api/controller/BaseApiController.php`、`server/app/adminapi/controller/BaseAdminController.php`
- 后端共享服务：`server/app/common/service/`
- 后端核心模型：`server/app/common/model/`
- 管理后台动态路由：`admin/src/router/index.ts`
- 管理后台用户态初始化：`admin/src/stores/modules/user.ts`
- 移动端接口封装：`uniapp/src/api/`
- SQL 变更：`server/sql/wedding/` 与 `server/sql/2.0.0.20260201/update.sql`
