# 婚庆服务预约管理系统

基于 LikeAdmin 二次开发的婚庆服务预约管理系统，当前仓库为前后端分离、多端协同的业务项目。根目录同时包含后端服务、管理后台、UniApp 主端、PC 前台，以及设计文档与历史 SQL 迁移脚本。

本 README 面向接手开发、联调、部署和排障的团队成员，重点说明当前仓库现状、运行方式、模块边界和数据库口径。子目录中的 `README` 多为模板或上游文档，根 README 以当前仓库代码和最新 SQL 脚本为准。

## 项目概览

当前主仓库包含以下核心模块：

| 模块 | 目录 | 说明 |
| --- | --- | --- |
| 后端服务 | `server` | ThinkPHP 8 多应用后端，承载管理端与客户端 API |
| 管理后台 | `admin` | Vue 3 + Element Plus 管理端，包含运营后台、装修、人员管理、订单/评价/通知等能力 |
| UniApp 主端 | `uniapp` | 用户端主入口，同时包含服务人员中心和移动管理员驾驶舱 |
| PC 前台 | `pc` | Nuxt 3 轻量前台，当前以首页、资讯、协议页、用户中心为主 |
| 设计文档 | `docs` | 当前主要保留 `UI设计规划.md` |

当前业务主线已经收敛为单服务直购链路：

`人员详情 -> 选择套餐/附加服务 -> 档期确认 -> 直接下单 -> 支付 -> 履约 -> 评价`

## 当前已落地的核心能力

以下内容是根据页面目录、后端控制器、业务逻辑和最新 SQL 迁移脚本确认后的当前能力。

### 业务能力

- 服务人员管理：服务分类、风格标签、人员资料、证书、作品、推荐位、轮播图配置。
- 服务商品体系：套餐、人员专属套餐、附加服务、地区价配置。
- 档期管理：档期日历、档期规则、服务人员中心档期维护、候补与候补通知。
- 交易链路：订单预览、直接下单、支付、线下支付凭证、退款、订单状态流转。
- 订单变更：当前保留改期、加项、附加服务变更、暂停四类链路。
- 内容互动：动态发布、动态评论、评价、敏感词、评价申诉、消息通知。
- 多角色使用：普通用户下单与查看订单，服务人员通过服务人员中心管理资料/作品/套餐/附加服务/档期/订单，移动管理员通过小程序驾驶舱查看经营数据。
- 装修系统：管理后台存在页面装修、TabBar 配置和组件化页面部件配置能力。

### 当前边界说明

- “换人”变更能力已下线。仓库中仍保留部分申请入口、模型字段和管理逻辑兼容代码，但当前控制器和逻辑层均已明确返回下线提示，不应作为现行能力承诺。
- 售后工单、服务回访、客服等链路在仓库中仍有模型、控制器和 UniApp 页面支撑，但后台菜单默认被过滤为隐藏/离线状态。README 仅说明“存在相关代码链路”，不将其视为默认启用模块。
- 积分奖励、评价奖励、晒单奖励等能力在部分模型和逻辑层中仍有实现痕迹，是否启用以当前配置、菜单和联调结果为准。

## 当前不作为主能力的历史模块和遗留目录

以下模块或入口已不应被视为当前在线能力：

- 购物车、购物车方案、分享方案。
- 订单转让。
- 时间线模板、订单时间线。
- 优惠券。
- 独立套餐入口。
- 场次价格独立入口、独立套餐可用性入口、独立套餐预约日历入口。

说明：

- 仓库中仍可能看到相关页面目录、组件、旧脚本或装修链接配置，这些内容主要用于历史迁移、兼容占位或后续清理。
- 以当前后端控制器返回、菜单过滤逻辑和 `server/sql/2.0.0.20260201/update.sql` 中的清理结果为准，不以旧 README、旧页面目录或历史 SQL 命名判断模块是否在线。

## 技术栈

### 后端

- PHP `>= 8.0`
- ThinkPHP 8（`topthink/framework ^8.0.2`）
- ThinkORM
- MySQL
- Redis

### 管理后台

- Vue 3.5
- Vite 6
- Element Plus 2.9
- Pinia
- TypeScript 5

### UniApp 主端

- UniApp（Vue 3）
- Tuniao UI
- Pinia
- Tailwind CSS
- Node `>= 18.19.0`

### PC 前台

- Nuxt 3.6
- Element Plus
- Pinia

## 仓库结构

```text
wedding-management/
├─ admin/      管理后台
├─ docs/       设计文档
├─ pc/         PC 前台
├─ server/     ThinkPHP 后端
└─ uniapp/     UniApp 主端
```

后端核心结构：

```text
server/
├─ app/
│  ├─ adminapi/   管理端接口
│  ├─ api/        客户端接口
│  ├─ common/     公共模型、服务、枚举、逻辑
│  └─ index/      默认应用
├─ config/        后端配置
├─ public/        Web 根目录与安装器
└─ sql/           基础升级与婚庆业务迁移脚本
```

## 环境准备

建议统一准备以下环境：

- PHP 8.0 及以上
- Composer 2
- MySQL 5.7+ 或兼容版本
- Redis
- Node 18 及以上
- UniApp 开发建议使用 Node 18.19.0 及以上

## 本地开发

### 1. 后端 `server`

安装依赖：

```bash
cd server
composer install
```

后端环境变量重点关注以下配置项：

- `database.driver`
- `database.type`
- `database.hostname`
- `database.database`
- `database.username`
- `database.password`
- `database.hostport`
- `database.prefix`
- `cache.driver`
- `cache.host`
- `cache.port`
- `cache.password`
- `filesystem.driver`
- `app.host`

说明：

- 数据库连接配置来源于 `server/config/database.php`。
- 缓存配置来源于 `server/config/cache.php`，默认可使用文件缓存，如切换 Redis 需补齐 `cache.*`。
- 文件存储配置来源于 `server/config/filesystem.php`。
- Web 根目录应指向 `server/public`。

### 2. 管理后台 `admin`

安装依赖并启动：

```bash
cd admin
npm install
npm run dev
```

管理后台当前重点环境变量：

- `VITE_APP_BASE_URL`

说明：

- `admin/vite.config.ts` 中 `base` 为 `/admin/`。
- 生产构建后，产物会自动复制到 `server/public/admin`。

### 3. UniApp 主端 `uniapp`

安装依赖并启动 H5 调试：

```bash
cd uniapp
npm install
npm run dev:h5
```

如需构建其他平台，可使用：

```bash
npm run dev:mp-weixin
npm run build:mp-weixin
```

UniApp 当前重点环境变量：

- `VITE_APP_BASE_URL`

说明：

- `uniapp/vite.config.ts` 默认 H5 开发端口为 `8991`。
- H5 生产构建后，产物会自动复制到 `server/public/mobile`。
- 仓库中同时包含主用户端页面、服务人员中心页面和移动管理员驾驶舱页面。

### 4. PC 前台 `pc`

安装依赖并启动：

```bash
cd pc
npm install
npm run dev
```

PC 前台当前重点环境变量：

- `NUXT_API_PREFIX`
- `NUXT_BASE_URL`
- `NUXT_SSR`
- `NITRO_PORT`

说明：

- 变量读取逻辑位于 `pc/nuxt/env.ts`。
- 非 SSR 构建使用 `npm run build`。
- 如需 SSR 构建，配置 `NUXT_SSR` 后使用 `npm run build:ssr`。
- 构建产物会复制到 `server/public/pc`。

## 数据库初始化与升级

当前仓库存在两种常见接入路径，必须区分处理。

### 路径一：全新安装

适用于从空库开始安装基础系统。

- 使用安装器入口：`server/public/install/install.php`
- 安装器基础库位于：`server/public/install/db/`

说明：

- 这是基础安装路径，适合先完成 LikeAdmin 基础结构初始化。
- 如跳过安装器，需自行确认安装器目录中的基础 SQL 与当前环境是否匹配。

### 路径二：在 LikeAdmin 基础库上接入婚庆业务

适用于已有 LikeAdmin 基础库，继续接入当前婚庆业务结构与菜单。

主升级脚本：

- `server/sql/2.0.0.20260201/update.sql`

说明：

- 该脚本是当前仓库对应的完整幂等升级脚本，目标是把已有基础库升级到当前婚庆业务所依赖的结构。
- 该脚本中已经包含对已下线业务的清理，例如购物车、订单转让、时间线、优惠券等。

### 其他 SQL 脚本的定位

- `server/sql/wedding/047_upgrade_remote_schema_20260319.sql`
  - 用于结构补齐和远端库升级，不作为全新初始化首选脚本。
- `server/sql/wedding/*.sql`
  - 主要用于历史分步演进、问题修复、菜单迁移或单点补丁，不作为当前 README 的主安装路径。

## 构建与发布

当前各端构建产物落点如下：

| 模块 | 构建命令 | 发布目录 |
| --- | --- | --- |
| `admin` | `npm run build` | `server/public/admin` |
| `uniapp` H5 | `npm run build:h5` | `server/public/mobile` |
| `pc` 静态构建 | `npm run build` | `server/public/pc` |
| `pc` SSR 构建 | `npm run build:ssr` | `server/public/pc` |

说明：

- 上述复制动作由各端仓库内的构建脚本自动完成。
- 根 README 仅说明当前产物落点，不额外约定 Nginx、容器化或 CI/CD 流程。

## 多端职责补充说明

### 管理后台

当前管理后台主要覆盖以下方向：

- 工作台与经营概览。
- 服务分类、标签、人员、作品、套餐、附加服务、地区价管理。
- 档期、档期规则、候补管理。
- 订单、退款、订单变更、订单暂停。
- 动态、评价、敏感词、申诉、通知。
- 页面装修、TabBar、组件化页面配置。
- 基础设置、支付设置、存储配置、字典、系统日志等。

### UniApp 主端

当前 UniApp 不只是用户端，还包含两个明显扩展角色入口：

- 服务人员中心：资料、作品、套餐、附加服务、档期、订单、动态。
- 移动管理员驾驶舱：经营总览、订单统计、团队负载、收入趋势等。

### PC 前台

当前 PC 前台定位较轻，主要包含：

- 首页。
- 资讯列表与资讯详情。
- 协议页。
- 用户信息与收藏等基础页面。

不建议将当前 PC 端描述为完整业务前台主入口。

## 文档基准与维护建议

- 根 README 以当前仓库代码、当前页面目录、后端控制器、业务逻辑和最新 SQL 迁移结果为准。
- 子目录中的 `README` 多数仍保留模板内容，仅可作为框架或上游项目参考。
- 新增或下线业务模块时，优先同步更新本 README 中的“当前已落地能力”和“历史/遗留模块”两节，避免文档继续漂移。

## 参考资料

- 设计文档：`docs/UI设计规划.md`
- 后端升级脚本：`server/sql/2.0.0.20260201/update.sql`
- 远端结构补齐脚本：`server/sql/wedding/047_upgrade_remote_schema_20260319.sql`
