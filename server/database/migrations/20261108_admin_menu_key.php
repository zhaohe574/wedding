<?php
declare(strict_types=1);

/**
 * 后台菜单「单一事实源」地基（B0）。
 *
 * 做三件事，全部是纯新增，不动任何既有菜单数据：
 *   1) la_system_menu 加 menu_key 列（+ 唯一索引），作为菜单的稳定身份；
 *      menu_key IS NULL 的行 = 「未托管」，同步命令永不改写它们 —— 这是不误伤
 *      运营手工菜单的硬保证。
 *   2) 建 la_system_menu_snapshot / la_system_role_menu_snapshot 两张快照表，
 *      供 admin_menu:sync --apply / --rollback 按 batch 还原。
 *   3) 用 database/menu/legacy_id_map.php 把既有 M/C 节点回填到对应 key。
 *
 * 默认只打印将执行的语句；加 --apply 才落库。
 *
 * 用法：
 *   php server/database/migrations/20261108_admin_menu_key.php
 *   php server/database/migrations/20261108_admin_menu_key.php --apply
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--test-database=')) {
        continue;
    }
    $name = substr($argument, strlen('--test-database='));
    $port = (int)getenv('WEDDING_TEST_MYSQL_PORT');
    if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $name) || $port <= 0 || $port === 3306) {
        throw new RuntimeException('仅允许独立本机测试数据库');
    }
    $app->config->set(['default' => 'mysql', 'connections' => ['mysql' => [
        'type' => 'mysql', 'hostname' => '127.0.0.1', 'hostport' => $port, 'database' => $name,
        'username' => 'root', 'password' => (string)getenv('WEDDING_TEST_MYSQL_PASSWORD'), 'charset' => 'utf8mb4', 'prefix' => 'qa_', 'debug' => false,
    ]]], 'database');
}

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$db = think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库前缀不合法');
}

$apply = in_array('--apply', $argv, true);
$menuTable = $prefix . 'system_menu';
$roleMenuTable = $prefix . 'system_role_menu';
$menuSnapshot = $prefix . 'system_menu_snapshot';
$roleMenuSnapshot = $prefix . 'system_role_menu_snapshot';

$idMap = require dirname(__DIR__) . '/menu/legacy_id_map.php';
if (!is_array($idMap) || !$idMap) {
    throw new RuntimeException('legacy_id_map.php 为空，请先运行 node server/database/menu/bootstrap.mjs');
}

$statements = [];

// ---------- 1. menu_key 列 ----------
$hasColumn = (int)$db->query(
    "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'menu_key'",
    [$menuTable]
)[0]['c'] > 0;

if (!$hasColumn) {
    $statements[] = "ALTER TABLE `{$menuTable}`
        ADD COLUMN `menu_key` VARCHAR(96) NULL DEFAULT NULL COMMENT '菜单稳定身份（见 database/menu/menu_manifest.php）；NULL=未托管' AFTER `id`";
}

$hasIndex = (int)$db->query(
    "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = 'uk_menu_key'",
    [$menuTable]
)[0]['c'] > 0;

if (!$hasIndex) {
    // MySQL 唯一索引允许多个 NULL，未托管行不会互相冲突
    $statements[] = "ALTER TABLE `{$menuTable}` ADD UNIQUE KEY `uk_menu_key` (`menu_key`)";
}

// ---------- 2. 快照表 ----------
$statements[] = "CREATE TABLE IF NOT EXISTS `{$menuSnapshot}` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_id` VARCHAR(64) NOT NULL COMMENT '批次标识，如 20261108B2',
    `menu_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(16) NOT NULL COMMENT 'insert/update/retire',
    `payload` JSON NOT NULL COMMENT '变更前整行（update/retire）或变更后整行（insert）',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_batch` (`batch_id`),
    KEY `idx_menu` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='菜单同步快照（回滚用）'";

$statements[] = "CREATE TABLE IF NOT EXISTS `{$roleMenuSnapshot}` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_id` VARCHAR(64) NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `menu_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(16) NOT NULL COMMENT 'grant（本批新增的授权）',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_batch` (`batch_id`),
    KEY `idx_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色授权快照（回滚用）'";

echo "将执行 " . count($statements) . " 条结构变更：\n";
foreach ($statements as $sql) {
    echo "  - " . preg_replace('/\s+/', ' ', mb_substr($sql, 0, 110)) . "...\n";
}

// ---------- 3. 回填 ----------
// 列刚加（或还没加）时不能 SELECT menu_key，按列是否存在决定字段列表
$field = $hasColumn ? 'id,type,name,component,menu_key' : 'id,type,name,component';
$rows = $db->table($menuTable)->field($field)->select()->toArray();
$byId = [];
foreach ($rows as $row) {
    $byId[(string)$row['id']] = $row;
}

$backfill = [];
$skipped = [];
foreach ($idMap as $id => $key) {
    $row = $byId[(string)$id] ?? null;
    if (!$row) {
        $skipped[] = "{$id}（库中不存在）";
        continue;
    }
    if (($row['menu_key'] ?? null) === $key) {
        continue;
    }
    // 只回填 M / C；A 型按钮不纳入托管
    if (!in_array($row['type'], ['M', 'C'], true)) {
        continue;
    }
    $backfill[] = ['id' => (int)$id, 'key' => (string)$key];
}

echo "待回填 menu_key：" . count($backfill) . " 条";
if ($skipped) {
    echo "（映射中 " . count($skipped) . " 条在本库缺失，可能是升级库差异）";
}
echo "\n";

if (!$apply) {
    echo "\n[DRY-RUN] 未落库。加 --apply 执行。\n";
    exit(0);
}

$db->startTrans();
try {
    foreach ($statements as $sql) {
        $db->execute($sql);
    }
    // 结构变了但 ThinkPHP 的字段缓存还是旧的（update 会报 fields not exists），
    // 回填直接用原生 SQL 绕开字段校验。
    $updateSql = "UPDATE `{$menuTable}` SET `menu_key` = ? WHERE `id` = ?";
    foreach ($backfill as $item) {
        $db->execute($updateSql, [$item['key'], $item['id']]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

echo "已落库：结构 " . count($statements) . " 条，回填 " . count($backfill) . " 条。\n";
$nullCount = (int)$db->query("SELECT COUNT(*) AS c FROM `{$menuTable}` WHERE `menu_key` IS NULL")[0]['c'];
echo "未托管行（menu_key IS NULL）：{$nullCount}\n";
