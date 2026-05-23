# CI/CD、质量门禁、灰度与回滚

## 1. 子项目职责

| 目录 | 角色 | 关键命令 | 发布产物 |
| --- | --- | --- | --- |
| `server` | ThinkPHP8 多应用后端 | `php -l`、Composer autoload | `server/` 与 `server/public` 静态资源 |
| `admin` | Vue3/Vite 管理后台 | `npm run type-check:active`、`npm run lint`、`npm run build` | `server/public/gelinshe0318` |
| `uniapp` | 小程序/H5 用户端 | `npm run validate:all`、`npm run type-check:active`、目标端 build | H5 到 `server/public/mobile`，小程序上传微信平台 |
| `pc` | Nuxt3 PC 站点 | `npm run quality`（当前为 package 配置基线）、`npm run lint`/`npm run type-check`（已暴露历史债务，暂不阻断）、`npm run build` | `server/public/pc` |

注意：当前 admin 实际发布目录是 `server/public/gelinshe0318`，不是通用 `admin` 目录，部署文档和 Nginx 配置必须保持一致。

## 2. PR 质量门禁

最低门禁：

1. PHP 语法：变更的 `server/**/*.php` 必须 `php -l` 通过。
2. Admin：涉及 `admin` 时运行 `npm run type-check:active`；提交前可运行 lint 但不要自动格式化无关文件。
3. Uniapp：涉及 `uniapp` 时运行 `npm run validate:all`；关键页面变更补小程序/H5 手工冒烟。
4. PC：涉及 `pc` 时运行 `npm run quality`；`npm run lint` 当前存在历史未使用变量/格式债务，`npm run type-check` 当前存在历史 Nuxt/TS 类型债务，二者需单独治理后再升为阻断门禁。
5. SQL：涉及 `server/sql` 或 `server/public/install/db/like.sql` 时必须说明升级、回滚和预检查。
6. 核心可靠性：涉及订单、支付、档期时必须补并发抢档、重复回调、取消后回调用例说明。

## 3. 发布步骤

1. 代码冻结：确认无未合并 P0 修复和 SQL 漏洞。
2. 备份：备份数据库、`server/public/gelinshe0318`、`server/public/mobile`、`server/public/pc`。
3. 预发：部署同版本，执行接口与关键页冒烟。
4. 迁移：按 `docs/ops/database-migration.md` 执行增量 SQL。
5. 灰度：先开放内部账号/低流量入口；重点观测支付回调、订单创建、档期锁异常日志。
6. 全量：确认 30-60 分钟无新增 P0 后放量。

## 4. 回滚策略

| 变更类型 | 回滚方式 | 备注 |
| --- | --- | --- |
| 前端静态资源 | 恢复备份目录或回退发布包 | admin/mobile/pc 三个 public 子目录分别备份。 |
| 后端代码 | 回退 Git tag/发布包，重启 PHP-FPM/服务 | 若已执行不可逆 SQL，不回滚数据，只回滚兼容代码。 |
| 数据库新增表 | 无业务数据时执行 rollback SQL | 有数据时保留表，应用层隐藏入口。 |
| 支付/订单异常数据 | 补偿单/人工审批 | 禁止直接 SQL 改状态。 |
| 配置/密钥 | 恢复上一版本 `.env` 或配置中心版本 | `.env` 不进入 Git。 |

## 5. 观测项

- 后端响应体和响应头均包含 `request_id`；客服反馈问题必须收集 request_id。
- 支付回调失败/异常支付：搜索 `pay_callback_reject`、`pay_exception`、`transaction_id`。
- 档期锁：关注锁超时、同档期并发失败率、`lock_expire_time`。
- 订单创建：关注事务失败、档期释放失败、重复订单。
- 问卷：关注任务重复提交、版本快照缺失。

## 6. GitHub Actions

`.github/workflows/quality.yml` 提供跨端基础门禁。若依赖安装耗时或私有源不可用，可先在自建 CI 中复用同一命令。
