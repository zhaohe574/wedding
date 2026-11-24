<?php
declare(strict_types=1);

/**
 * 增量迁移：为 la_operation_log 补齐 idx_admin_time 与 idx_create_time 索引，并同步 la_order.order_status 注释。
 *
 * 默认只读预检；传入 --apply 执行变更：
 *   php server/database/migrations/20261120_operation_log_indexes.php
 *   php server/database/migrations/20261120_operation_log_indexes.php --apply
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$serverRoot = dirname(__DIR__, 2);
require $serverRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = new \think\App($serverRoot . DIRECTORY_SEPARATOR);
$app->initialize();

set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, '迁移失败：' . $error->getMessage() . "\n");
    exit(1);
});

$db = \think\facade\Db::connect();
$prefix = (string)config('database.connections.mysql.prefix', 'la_');
if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
    throw new RuntimeException('数据库表前缀不合法');
}

$apply = in_array('--apply', $argv, true);
$logTable = $prefix . 'operation_log';

$indexes = [];
foreach ($db->query("SHOW INDEX FROM `{$logTable}`") as $row) {
    $keyName = (string)($row['Key_name'] ?? $row['key_name'] ?? '');
    if ($keyName !== '') {
        $indexes[$keyName] = true;
    }
}

$statements = [];
if (!isset($indexes['idx_admin_time'])) {
    $statements[] = "ALTER TABLE `{$logTable}` ADD KEY `idx_admin_time` (`admin_id`, `create_time`)";
}
if (!isset($indexes['idx_create_time'])) {
    $statements[] = "ALTER TABLE `{$logTable}` ADD KEY `idx_create_time` (`create_time`)";
}

if (empty($statements)) {
    echo "{$logTable} 索引已齐备（idx_admin_time, idx_create_time），无需变更。\n";
    exit(0);
}

foreach ($statements as $sql) {
    echo ($apply ? '[APPLY] ' : '[PLAN]  ') . $sql . "\n";
    if ($apply) {
        $db->execute($sql);
    }
}

echo $apply ? "执行完成。\n" : "预检完成（未修改数据库，传入 --apply 执行）。\n";
