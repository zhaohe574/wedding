---
name: deploy-prod
description: >-
  Publish or update the wedding-management production server (server, admin, and pc only;
  never touch or build miniapp) with zero data loss, maintenance window, backups, ordered
  DB/menu migrations, and loopback + public verification. Use whenever the user asks to
  deploy, release, update, or hotfix the production server (正式机/线上发布/再发布一版).
---

# 正式机发布与更新指南（仅服务端 / 后台 / PC）

本技能用于将 `wedding-management` 本地最新代码安全发布或热更到正式机（`www.gelinshe.com`），**完整保留线上数据库与运行期文件**。

## 范围边界与安全红线

1. **只发布 `server/` + `admin/`（`/gelinshe0318`）+ `pc/`（`/pc`）**。
   - **完全不管小程序（`newuniapp/`、`uniapp/`）**：不构建、不打包、不检测、不发布、不询问小程序，小程序由用户自行发布。
2. **凭据来源**：服务器 IP、SSH/sudo 账号密码从 `D:\2025-10-31\线上部署\各类信息.docx` 现场读取；数据库账号密码直接从正式机 `/www/wwwroot/gelinshe/.env` 读取。
   - **严禁**在回复、文档、脚本或仓库中输出或写入任何明文密码、Secret、API Key。
3. **绝对保留的线上文件/目录（严禁覆盖或清空）**：
   - `/www/wwwroot/gelinshe/.env`（权限保持 `600 www:www`）
   - `/www/wwwroot/gelinshe/config/install.lock`
   - `/www/wwwroot/gelinshe/public/uploads/`
   - `/www/wwwroot/gelinshe/public/MP_verify_*.txt` 与 `public/.well-known/`
   - `/www/wwwroot/gelinshe/runtime/`（仅清 `cache/`、`temp/`、`schema/`，不清日志与目录本身）
   - `/www/wwwroot/gelinshe/vendor/`（仅当规范化 LF 后的 `composer.lock` 与线上不一致时才更新）
   - 宝塔 Nginx 扩展配置（`/www/server/panel/vhost/nginx/extension/www.gelinshe.com/`）与定时任务脚本
4. **Redis 红线**：严禁执行 `FLUSHDB` / `FLUSHALL`，仅通过 PHP 清理 `ConfigCache` 与 `AdminAuthCache`。

## 正式机环境速查

- 站点目录：`/www/wwwroot/gelinshe`
- 运行环境：PHP 8.0（`/www/server/php/80/bin/php`）、PHP-FPM（`/etc/init.d/php-fpm-80 reload`）、MySQL 5.7（库名 `gelinshe`，表前缀 `la_`）、Redis、Nginx（宝塔）
- 维护开关：`/www/maintenance/gelinshe.flag`（创建后外部请求返回 503、宝塔 cron 自动跳过、仅本机 `127.0.0.1` 放行只读验收）
- 备份根目录：`/www/backup/`
- SSH 连接规范：使用 Python `paramiko`（带重试：最多 5 次、间隔 3 秒、`banner_timeout=30`）+ SFTP 传包；远程 Bash/PHP 脚本统一用 `base64` 写入 `/tmp/*.sh` 或 `/tmp/*.php` 后执行，避免反引号与引号转义问题。

## 发布模式选择（先盘点变更再动刀）

发布前先检查本地自上次发布以来变动的文件（`server/`、`admin/`、`pc/`、`shared/` 及 `server/database/migrations/`、`server/database/menu/menu_manifest.php`、`server/composer.lock`）：

| 模式 | 适用场景 | 打包与备份方式 |
| :--- | :--- | :--- |
| **单文件热更** | 仅改 1~2 个 PHP 文件、无前端构建、无 DB 迁移 | 备份原文件至 `/www/backup/gelinshe-hotfix-<日期>-<时分秒>/`，覆盖并 `chown www:www` + `php -l` + reload PHP-FPM + 验证 |
| **增量发布** | 仅改少量 PHP 文件和/或 `admin`（或仅 `pc`），无 DB 迁移 | 仅构建有改动的前端；只打包改动文件与对应前端产物目录；开启维护窗口备份受影响项后替换 |
| **全量更新** | 涉及多个后端模块、同时改动 `admin` 与 `pc`，或包含 DB 迁移 / 菜单清单变更 | 完整执行下方 6 步标准流程（含全量 `mysqldump` 与代码备份） |

---

## 标准发布步骤

### Step 1：本地预检与前端构建
1. 对本次变动的 PHP 文件执行 `php -l`（确保兼容 PHP 8.0，0 语法错误）。
2. 若 `admin/` 或 `shared/` 有改动，执行：
   ```powershell
   cd admin; npm run type-check; npm run build
   ```
   确认退出码为 0，产物自动同步至 `server/public/gelinshe0318`。
3. 若 `pc/` 有改动，执行：
   ```powershell
   cd pc; npm run build
   ```
   确认退出码为 0，产物自动同步至 `server/public/pc`（`nuxt generate` 构建期报 `/api/pc/config` 不可达属正常兜底，以退出码 0 和 `dist` 拷贝完成为准）。

### Step 2：本地打包并 SFTP 上传
1. 使用 Python `tarfile`（GNU 格式，二进制原样写入）打包到 `$env:TEMP\gelinshe_release_<日期>.tar.gz`：
   - **全量包含**：`app/`、`database/`、`extend/`、`route/`、`config/*.php`（**严禁包含 `config/install.lock`**）、`think`、`composer.json`、`composer.lock`、`public/index.php`、`public/router.php`、`public/robots.txt`、`public/nginx.htaccess`、`public/.htaccess`、`public/gelinshe0318/`、`public/pc/`、`public/resource/`、`public/static/`、`public/install/`。
   - **严格断言排除**：`.env`、`config/install.lock`、`public/uploads/`、`runtime/`、`vendor/`、`tests/`、`.git*`、`public/mobile/`。
2. 用 `paramiko` SFTP 上传到 `/tmp/gelinshe_release_<日期>.tar.gz`，核对远端与本地 SHA256 完全一致。

### Step 3：记录数据基线、开启维护窗口与全量备份
在正式机单次 SSH 脚本中执行：
1. 采集核心业务表发布前行数基线（`la_user`, `la_admin`, `la_staff`, `la_order`, `la_order_item`, `la_payment`, `la_refund`, `la_schedule`, `la_service_package`, `la_file`, `la_file_cate`, `la_config`, `la_system_role`, `la_system_role_menu`, `la_decorate_page`, `la_dynamic`, `la_activity_registration`, `la_couple_questionnaire`, `la_operation_log`）。
2. 开启维护窗口：`mkdir -p /www/maintenance && touch /www/maintenance/gelinshe.flag && sleep 2`。
3. 创建备份目录 `BACKUP_DIR="/www/backup/gelinshe-<日期>-<时分秒>"`：
   - **全量数据库备份**（必带 `--no-tablespaces`，避免线上无 `PROCESS` 权限报错）：
     ```bash
     MYSQL_PWD="$DB_PASS" mysqldump --no-tablespaces --single-transaction --routines --triggers \
       -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" "$DB_NAME" | gzip > "$BACKUP_DIR/gelinshe_db.sql.gz"
     gzip -t "$BACKUP_DIR/gelinshe_db.sql.gz"
     ```
     校验 `zgrep -c '^CREATE TABLE '` 不少于现网表数，否则立即中止。
   - **现网代码备份**：
     ```bash
     tar --exclude='public/uploads' --exclude='runtime' --exclude='vendor' \
       -czf "$BACKUP_DIR/gelinshe_code.tar.gz" -C /www/wwwroot/gelinshe .
     gzip -t "$BACKUP_DIR/gelinshe_code.tar.gz"
     ```

### Step 4：安全替换线上代码（保留数据与配置）
1. 解压到 `/tmp/gelinshe_stage`，再次断言不含 `.env`、`config/install.lock`、`public/uploads`。
2. 整目录干净替换（避免残留已删文件或旧版前端 hash 资源）：
   - `app`、`database`、`extend`、`route`、`public/install`（全量时）
   - `public/gelinshe0318`（本次构建了 `admin` 时）
   - `public/pc`（本次构建了 `pc` 时）
3. 覆盖 `config/*.php`（不动 `config/install.lock`）及入口文件，合并覆盖 `public/resource/`、`public/static/`。
4. 核对 `.env`、`config/install.lock`、`public/uploads/`、`public/MP_verify_*.txt`、`public/.well-known/`、`runtime/`、`vendor/` 全部存在。
5. 修正属主与清理旧编译缓存：
   - `chown -R www:www` 替换过的目录与 `runtime/`；`chmod 600 .env && chown www:www .env`。
   - `rm -rf runtime/cache/* runtime/temp/* runtime/schema/*`。

### Step 5：按顺序执行增量数据库迁移与菜单同步（如有）
1. **先跑 DDL / 索引 / 数据初始化迁移**（核查 MySQL 5.7 兼容；PHP 脚本加 `--apply`，SQL 脚本用 `mysql --default-character-set=utf8mb4` 导入）。
2. **再同步后台菜单清单**（如 `menu_manifest.php` 有变更）：
   - 先只读预览：`/www/server/php/80/bin/php think admin_menu:sync`，确认新增/更新项完全符合预期、无异常退役或误标。
   - 再落库：`/www/server/php/80/bin/php think admin_menu:sync --apply --batch=<YYYYMMDD>prod`。
3. **最后导入依赖新菜单 `perms` 的按钮权限补登记 `.sql`**，再复跑一次 `php think admin_menu:sync` 确认 **0 新增、0 更新、0 孤儿授权**。
4. 清理 `ConfigCache` 与 `AdminAuthCache`，并**务必执行**：
   ```bash
   chown -R www:www /www/wwwroot/gelinshe/runtime /www/wwwroot/gelinshe/database
   ```
   （避免 `root` 跑迁移生成的当天日志文件导致 `www` 用户写日志报 `Permission denied`。）

### Step 6：重载 PHP-FPM、维护期内验收、关闭维护窗口与公网复验
1. 重载 PHP-FPM 清理 OPcache：`/etc/init.d/php-fpm-80 reload`。
2. **维护期内以 `www` 用户运行态验收**（任一项失败必须保持维护开关开启并立刻排查）：
   - 安全门控：确认 `\app\common\service\pay\PayMockService::isEnabled() === false`。
   - 定时任务：`/usr/sbin/runuser -u www -- /www/server/php/80/bin/php /www/wwwroot/gelinshe/think crontab` 退出码 0。
   - 关键逻辑：调用 `\app\adminapi\logic\WorkbenchLogic::index()` 无异常。
   - 本机回环验收（`https://127.0.0.1`，`Host: www.gelinshe.com`）：
     - `GET /gelinshe0318/`、`GET /pc/` 返回 HTTP 200
     - `GET /adminapi/config/getConfig`、`GET /api/pc/config`、`GET /api/pc/index`、`GET /api/index/index`、`GET /api/index/decorate?id=1`、`GET /api/index/policy?type=privacy` 均返回 `code: 1`。
3. 核对发布后核心业务表行数与 Step 3 基线一致（零数据丢失）。
4. 全部通过后关闭维护窗口并清理临时文件：
   - `rm -f /www/maintenance/gelinshe.flag`
   - 清理远端 `/tmp/gelinshe_*`、`/tmp/deploy_*`、`/tmp/*.php` 及本地 `$env:TEMP\gelinshe_*.tar.gz`。
5. **公网复验**：访问 `https://www.gelinshe.com/gelinshe0318/`、`https://www.gelinshe.com/pc/`、`https://www.gelinshe.com/adminapi/config/getConfig`、`https://www.gelinshe.com/api/pc/index`、`https://www.gelinshe.com/api/index/decorate?id=1`，均返回 HTTP 200 / `code: 1`。
6. 汇报：变更范围、备份路径、迁移结果、核心表行数核对与公网验收结果（不输出任何明文凭据）。

## 故障排查与回滚速查
- 多应用日志目录：`/www/wwwroot/gelinshe/runtime/adminapi/log/`、`/www/wwwroot/gelinshe/runtime/api/log/`、`/www/wwwroot/gelinshe/runtime/log/`（按 `request_id` 全局 `grep`）。
- 菜单回滚：`php think admin_menu:sync --rollback=<batch>`。
- 代码/库回滚：开启 `/www/maintenance/gelinshe.flag` → 用对应备份目录下的 `gelinshe_code.tar.gz` 与 `gelinshe_db.sql.gz` 还原 → `chown -R www:www` → reload PHP-FPM → 验收后摘维护开关。
