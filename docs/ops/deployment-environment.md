# 部署环境与本地差异说明

## 1. 运行时版本基线

| 组件 | 建议版本 | 当前观察 |
| --- | --- | --- |
| PHP | 8.0+，生产建议 8.1/8.2 LTS 兼容验证后升级 | 本机 `php -v` 为 8.0.2。 |
| ThinkPHP | 8.x | `server/composer.json` 要求 `topthink/framework ^8.0.2`。 |
| Node.js | 20 LTS 优先；uniapp 至少 18.19 | `uniapp/package.json` 声明 `>=18.19.0`。 |
| MySQL | 5.7+/8.0 | 增量 SQL 应避免依赖单一版本特性，或写明版本要求。 |
| Redis | 5+ | 档期锁依赖 Redis 锁服务。 |

## 2. ImageMagick / Imagick 差异

本机执行 PHP 命令时出现：

```text
Imagick was compiled against ImageMagick version 1809 but version 1810 is loaded.
```

影响：

- `php -l` 仍可运行，但图片处理、验证码、上传缩略图等场景可能出现运行时差异。
- 若生产和本地 ImageMagick/Imagick ABI 不一致，问题可能只在线上暴露。

处理建议：

1. 生产环境固定 PHP、Imagick 扩展、ImageMagick 动态库版本，并记录安装包来源。
2. CI 的 PHP lint 不强依赖 Imagick；图片处理集成测试应在与生产一致的镜像中运行。
3. 本地修复：重新安装与当前 ImageMagick 版本匹配的 Imagick 扩展，或降级/固定 ImageMagick 动态库。
4. 回滚：若发布后图片处理异常，先回滚运行时镜像/扩展版本，再回滚业务代码。

## 3. 静态资源发布路径

| 子项目 | 构建命令 | 实际复制目录 |
| --- | --- | --- |
| admin | `npm run build` | `server/public/gelinshe0318` |
| uniapp H5 | `npm run build:h5` | `server/public/mobile` |
| pc | `npm run build` 或 `npm run build:ssr` | `server/public/pc` |

风险：发布脚本会先删除目标目录再复制新产物。上线前必须备份对应 public 子目录；失败时可直接恢复备份目录。

## 4. 环境变量与密钥

- 根 `.gitignore` 已忽略 `.env`、`.env.*`，保留 `.env.example` / `*.example.env`。
- `server/.env`、`pc/.env` 等本地文件不得提交。
- 示例文件只保留变量名、默认非敏感值和注释，不放真实支付、短信、对象存储密钥。

## 5. 自动路由风险

当前后端仍依赖 ThinkPHP 自动路由和控制器命名约定。短期治理：

- 核心接口以 `docs/contracts/openapi-core.yaml` 作为契约源。
- 新增接口必须先写契约，再加控制器。
- 若后续启用强制路由，先对 `/api/order/*`、`/api/pay/*`、`/api/schedule/*`、`/api/couple_questionnaire/*` 小范围灰度。
