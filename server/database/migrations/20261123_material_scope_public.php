<?php
declare(strict_types=1);

/**
 * 素材按账号隔离 + 公共素材库：la_file_cate 结构升级与存量分组归属回填（2026-11-23）。
 *
 * 一、结构（幂等，缺失才补）：
 *   - 新增列 `is_public` tinyint(1) unsigned NOT NULL DEFAULT '0'（0=个人分组,1=公共分组）
 *   - 新增列 `admin_id` int(11) unsigned NOT NULL DEFAULT '0'（归属后台账号ID，0=公共）
 *   - 新增索引 idx_type_public_admin (`type`,`is_public`,`admin_id`)
 *
 * 二、存量分组归属回填（仅处理 delete_time IS NULL 且 is_public=0 AND admin_id=0 的记录）：
 *   - 顶层分组（pid=0）name 精确匹配 la_staff.name（admin_id>0 且未删除）
 *     或 la_admin.name / la_admin.account（未删除）时，
 *     该顶层分组及其全部递归子分组标记为 is_public=0, admin_id=<匹配到的admin_id>；
 *   - 其余未命中个人账号的存量分组（如「系统素材」）及其递归子分组、游离分组
 *     统一标记为 is_public=1, admin_id=0（公共分组）。
 *
 * 默认只读预览；确认无误后在维护窗口追加 --apply 落库（重复执行完全幂等）：
 *   php server/database/migrations/20261123_material_scope_public.php
 *   php server/database/migrations/20261123_material_scope_public.php --apply
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = new think\App(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
$app->initialize();

foreach ($argv as $argument) {
    if (!str_starts_with($argument, '--test-database=')) {
        continue;
    }
    $name = substr($argument, strlen('--test-database='));
    $port = (int) getenv('WEDDING_TEST_MYSQL_PORT');
    if (!preg_match('/^wedding_binding_[0-9]+_[0-9]+$/', $name) || $port <= 0 || $port === 3306) {
        throw new RuntimeException('仅允许独立本机测试数据库');
    }
    $app->config->set([
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'type' => 'mysql',
                'hostname' => '127.0.0.1',
                'hostport' => $port,
                'database' => $name,
                'username' => 'root',
                'password' => (string) getenv('WEDDING_TEST_MYSQL_PASSWORD'),
                'charset' => 'utf8mb4',
                'prefix' => 'qa_',
                'debug' => false,
            ],
        ],
    ], 'database');
}

set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
});

$db = think\facade\Db::connect();
$prefix = (string) config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库前缀不合法');
}

$apply = in_array('--apply', $argv, true);
$cateTable = $prefix . 'file_cate';
$cateExists = $db->query("SHOW TABLES LIKE '{$cateTable}'");
if (empty($cateExists)) {
    throw new RuntimeException("表 `{$cateTable}` 不存在，请先完成基础安装");
}

// ---------------------------------------------------------------------------
// 一、结构升级（缺失才执行，幂等）
// ---------------------------------------------------------------------------
$alterParts = [];

$columnDefinitions = [
    'is_public' => "ADD COLUMN `is_public` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否公共分组[0=个人分组,1=公共分组]' AFTER `name`",
    'admin_id' => "ADD COLUMN `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '归属后台账号ID(0=公共)' AFTER `is_public`",
];
foreach ($columnDefinitions as $column => $definition) {
    $exists = $db->query("SHOW COLUMNS FROM `{$cateTable}` LIKE '{$column}'");
    if (empty($exists)) {
        echo "[补列] `{$cateTable}`.{$column}\n";
        $alterParts[] = $definition;
    } else {
        echo "[已存在] `{$cateTable}`.{$column}\n";
    }
}

$indexName = 'idx_type_public_admin';
$indexCount = (int) ($db->query(
    "SELECT COUNT(*) AS cnt FROM information_schema.statistics"
    . " WHERE table_schema = DATABASE() AND table_name = '{$cateTable}' AND index_name = '{$indexName}'"
)[0]['cnt'] ?? 0);
if ($indexCount === 0) {
    echo "[补索引] `{$cateTable}`.{$indexName}\n";
    if (!empty($alterParts)) {
        $alterParts[] = "ADD INDEX `{$indexName}` (`type`,`is_public`,`admin_id`) USING BTREE";
        $sql = "ALTER TABLE `{$cateTable}` " . implode(', ', $alterParts);
        echo $sql . ";\n";
        if ($apply) {
            $db->execute($sql);
            echo "已补齐结构与索引\n";
        }
    } else {
        $sql = "ALTER TABLE `{$cateTable}` ADD INDEX `{$indexName}` (`type`,`is_public`,`admin_id`) USING BTREE";
        echo $sql . ";\n";
        if ($apply) {
            $db->execute($sql);
            echo "已补齐索引\n";
        }
    }
} else {
    echo "[已存在] 索引 `{$cateTable}`.{$indexName}\n";
    if (!empty($alterParts)) {
        $sql = "ALTER TABLE `{$cateTable}` " . implode(', ', $alterParts);
        echo $sql . ";\n";
        if ($apply) {
            $db->execute($sql);
            echo "已补齐缺失列\n";
        }
    }
}

// ---------------------------------------------------------------------------
// 二、存量分组归属回填
// ---------------------------------------------------------------------------
$hasScopeCols = !empty($db->query("SHOW COLUMNS FROM `{$cateTable}` LIKE 'is_public'"))
    && !empty($db->query("SHOW COLUMNS FROM `{$cateTable}` LIKE 'admin_id'"));
$rows = $db->query(
    "SELECT id, pid, name FROM `{$cateTable}`"
    . ' WHERE delete_time IS NULL'
    . ($hasScopeCols ? ' AND is_public = 0 AND admin_id = 0' : '')
    . ' ORDER BY id ASC'
);

if (empty($rows)) {
    echo "[回填] 无待回填的存量分组（已处理过或无数据）\n";
    if (!$apply) {
        echo "[只读预览] 未修改数据库；确认无误后追加 --apply 执行。\n";
    }
    exit(0);
}

echo "[回填] 待处理存量分组 " . count($rows) . " 条\n";

// 子分组索引（仅限本轮候选集内）
$childrenMap = [];
foreach ($rows as $row) {
    $childrenMap[(int) $row['pid']][] = (int) $row['id'];
}

// 服务人员名 -> admin_id（同名取最小 admin_id）
$staffNameMap = [];
$staffRows = $db->query(
    "SELECT name, admin_id FROM `{$prefix}staff`"
    . " WHERE admin_id > 0 AND delete_time IS NULL ORDER BY admin_id ASC"
);
foreach ($staffRows as $staff) {
    $name = trim((string) ($staff['name'] ?? ''));
    $adminId = (int) ($staff['admin_id'] ?? 0);
    if ($name === '' || $adminId <= 0 || isset($staffNameMap[$name])) {
        continue;
    }
    $staffNameMap[$name] = $adminId;
}

// 后台账号名/账号名 -> admin_id（同名取最小 id）
$adminNameMap = [];
$adminRows = $db->query(
    "SELECT id, name, account FROM `{$prefix}admin` WHERE delete_time IS NULL ORDER BY id ASC"
);
foreach ($adminRows as $admin) {
    foreach ([trim((string) ($admin['name'] ?? '')), trim((string) ($admin['account'] ?? ''))] as $name) {
        if ($name === '' || isset($adminNameMap[$name])) {
            continue;
        }
        $adminNameMap[$name] = (int) ($admin['id'] ?? 0);
    }
}

$claimed = [];
$pendingUpdates = 0;
$appliedUpdates = 0;
$personalGroups = 0;
$publicGroups = 0;

$planGroup = static function (int $groupId, int $targetIsPublic, int $targetAdminId, string $reason)
use ($db, $apply, &$claimed, &$pendingUpdates, &$appliedUpdates, &$personalGroups, &$publicGroups, $cateTable): void {
    if (isset($claimed[$groupId])) {
        return;
    }
    $claimed[$groupId] = true;
    $label = $targetIsPublic === 1 ? "公共(is_public=1,admin_id=0)" : "个人(is_public=0,admin_id={$targetAdminId})";
    echo "  [待标记] 分组 #{$groupId} => {$label}（{$reason}）\n";
    $pendingUpdates++;
    if ($targetIsPublic === 1) {
        $publicGroups++;
    } else {
        $personalGroups++;
    }
    if ($apply) {
        $db->execute(
            "UPDATE `{$cateTable}` SET `is_public` = ?, `admin_id` = ?, `update_time` = ?"
            . " WHERE `id` = ? AND `delete_time` IS NULL AND `is_public` = 0 AND `admin_id` = 0",
            [$targetIsPublic, $targetAdminId, time(), $groupId]
        );
        $appliedUpdates++;
    }
};

$collectDescendantIds = static function (int $groupId) use (&$childrenMap): array {
    $ids = [];
    $stack = [$groupId];
    while (!empty($stack)) {
        $current = array_pop($stack);
        foreach ($childrenMap[$current] ?? [] as $childId) {
            $ids[] = $childId;
            $stack[] = $childId;
        }
    }
    return $ids;
};

foreach ($rows as $row) {
    if ((int) $row['pid'] !== 0) {
        continue;
    }

    $groupId = (int) $row['id'];
    $name = trim((string) ($row['name'] ?? ''));
    $ownerAdminId = $staffNameMap[$name] ?? ($adminNameMap[$name] ?? 0);

    $reason = $ownerAdminId > 0 ? "顶层分组「{$name}」匹配账号" : "顶层分组「{$name}」未匹配个人账号";
    $planGroup($groupId, $ownerAdminId > 0 ? 0 : 1, $ownerAdminId, $reason);

    if ($ownerAdminId > 0) {
        foreach ($collectDescendantIds($groupId) as $childId) {
            $planGroup($childId, 0, $ownerAdminId, "继承顶层分组「{$name}」");
        }
    }
}

// 游离分组（父级不存在或已删除、且未被顶层分组继承链覆盖）统一归为公共
foreach ($rows as $row) {
    if (isset($claimed[(int) $row['id']])) {
        continue;
    }
    $planGroup((int) $row['id'], 1, 0, '未匹配个人账号的游离分组');
}

echo "\n汇总：个人分组 {$personalGroups} 条，公共分组 {$publicGroups} 条，"
    . "待更新 {$pendingUpdates} 条，已更新 {$appliedUpdates} 条。\n";

if (!$apply) {
    echo "[只读预览] 未修改数据库；确认无误后追加 --apply 执行更新。\n";
}
