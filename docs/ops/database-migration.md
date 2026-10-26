# 数据库迁移规范

本文约定婚庆服务预约系统（`server/`，ThinkPHP 8.1.4 多应用，表前缀 `la_`）的数据库变更流程。
`server/app/common/service/RequestContextService.php` 等代码中的注释引用本文，说明「可选落表」时的做法。

## 1. 两条数据来源，必须双向同步

| 来源 | 用途 | 位置 |
| --- | --- | --- |
| 安装 SQL | 全新部署时一次性建库建表、写入初始菜单与权限 | `server/public/install/db/like.sql` |
| 迁移脚本 | 存量环境升级（可重复执行） | `server/database/migrations/*.php` |

**硬性要求**：任何新增表 / 新增后台菜单权限，必须**同时**改这两处。

- 只改安装 SQL：存量环境升级后功能不可用（表不存在 / 菜单 403）。
- 只改迁移脚本：全新安装后功能不可用（同上）。
- 迁移脚本头部需写明「与 `like.sql` 保持同步」的注释，便于后续维护者发现关联。

现有样例：`server/database/migrations/20260922_mnp_subscribe_notification_init.php`
（微信小程序订阅消息模板表 + 发送日志表），其表结构、默认场景数据与后台菜单
`notification.mnpNotification/*` 均已在 `like.sql` 中同步。

## 2. 迁移脚本写法约定

1. **幂等**：所有 DDL 使用 `CREATE TABLE IF NOT EXISTS`；数据初始化先查后写（`where(...)->find()` 再决定 `insert` / `update`）。
2. **默认只读**：不带 `--apply` 时仅打印将要执行的变更（Dry Run），便于评审与上线前预检。
3. **禁止破坏性语句**：不在迁移中执行 `DROP TABLE` / `DROP COLUMN` / 无 `WHERE` 的 `UPDATE` / `DELETE`。
   确需删列时，先新增新列并双写，再在后续版本单独下线旧列。
4. **表前缀**：脚本内通过 `config('database.connections.mysql.prefix')` 取前缀并做 `^[a-zA-Z0-9_]*$` 校验，
   不得硬编码 `la_`。
5. **测试库隔离**：连接测试库的参数需带独立库名（如 `--test-database=wedding_binding_xxx`）且端口不得为 3306，
   防止误连生产。
6. **菜单与权限**：新增后台页面时，`la_system_menu` 的 `perms` 必须与控制器实际 `action` 名一致
   （形如 `notification.mnpNotification/templateList`），并同步 `la_system_role_menu` 中对应角色的授权记录。
   未登记权限的接口在 `AuthMiddleware` 下会被默认拒绝。

## 3. 上线流程

```bash
# 1) 预检（只读）
php server/database/migrations/<脚本名>.php

# 2) 备份（生产必做）
mysqldump --single-transaction --routines -u<user> -p <db> > backup_$(date +%Y%m%d%H%M).sql

# 3) 执行
php server/database/migrations/<脚本名>.php --apply

# 4) 校验：表存在 + 后台菜单可见 + 接口不 403
mysql -u<user> -p -e "SHOW TABLES LIKE 'la_wechat_mnp%'" <db>
```

全新部署则直接导入 `like.sql`，随后用非超管账号验证新菜单可见、接口不返回 403。

## 4. `request_id` 相关表（可选）

`RequestContextService` 目前只依赖请求头 `X-Request-Id` 与日志，**不需要任何数据库变更**，旧库可直接上线。
若后续需要把 `request_id` 独立落表用于链路追踪，按以下规范执行：

```sql
CREATE TABLE IF NOT EXISTS `la_request_trace` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` varchar(64) NOT NULL DEFAULT '' COMMENT '链路追踪ID',
  `app` varchar(32) NOT NULL DEFAULT '' COMMENT '应用（adminapi/api/index）',
  `uri` varchar(255) NOT NULL DEFAULT '' COMMENT '请求路径',
  `user_type` varchar(16) NOT NULL DEFAULT '' COMMENT 'admin/user/guest',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='请求链路追踪';
```

同时：① 追加到 `like.sql` 末尾；② 新建对应迁移脚本；③ 配置定时清理（建议保留 7 天），
避免该表无限增长。

## 5. 回滚

迁移脚本不提供自动回滚。回滚方式是「用上线前的备份还原 + 回退代码」，因此：

- 上线前必须备份；
- 破坏性变更（删列、改类型）必须拆成多个版本分批上线；
- 迁移执行记录写进 `docs/线上部署记录-*.md`，包含脚本名、执行时间、校验结果。
